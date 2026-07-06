<?php

declare(strict_types=1);

/*
 * build.php: the whole "blog engine", such as it is.
 *
 * Reads Markdown posts from journal/_posts/, renders one static HTML page per
 * article plus the journal index, injects the latest few as teasers into the
 * home page, and regenerates the sitemap. Run it locally, commit the output.
 *
 *     composer install   # once, restores vendor/
 *     php build.php       # rebuilds the journal, then re-run before committing
 *
 * The live site never runs this: it serves the committed HTML, nothing else.
 */

require __DIR__ . '/vendor/autoload.php';

use League\CommonMark\CommonMarkConverter;

const ROOT          = __DIR__;
const POSTS_DIR     = ROOT . '/journal/_posts';
const TEMPLATES_DIR = ROOT . '/journal/_templates';
const BASE_URL      = 'https://labault.dev';
const TEASER_COUNT  = 3; // how many recent posts show up on the home page

const MONTHS_FR = [
    1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
];

const IMAGE_MODEL   = 'claude-opus-4-8';               // used only when generating a missing medallion
const ANTHROPIC_API = 'https://api.anthropic.com/v1/messages';

/*
 * The house style for a journal medallion, handed to the model verbatim.
 * Everything that makes the series coherent lives here; only the emblem, the
 * legend and the aria-label change from one article to the next. Edit this to
 * evolve the look: it's the closest thing to a written charte for the covers.
 */
const MEDALLION_BRIEF = <<<'PROMPT'
You draw a single cover illustration for a blog article, as one self-contained SVG.

Hard requirements (a build script writes your output straight to a .svg file):
- Output ONLY the SVG. No prose, no markdown fences, nothing before <svg or after </svg>.
- Root element: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" role="img" aria-label="...">.
- 100% vector. No <image>, no bitmap, no external reference, no <script>.
- Write the aria-label in French, describing the scene in one sentence.

The series style (every article in this journal shares it), so match it closely:
- Background: deep night-blue radial gradient (roughly #21375A -> #13223B -> #091221), with a faint warm halo behind the centre.
- Foreground: a single round bronze medallion in relief, centred, filling most of the height.
- Bronze face: a multi-stop radial gradient, ~7 stops, light gold at the top-left (about #FCE9CE) down to a dark umber shadow (about #552E15).
- Rim: several levels, a bright outer bevel, fine reeding (radial ticks) around the edge, then a dark sunken groove, with a thin verdigris patina line.
- Engraving: concentric engine-turned guilloche lines on the crown and in the central field, plus a soft inner shadow.
- A discreet verdigris (sage-green) tint in the deepest grooves.
- A specular highlight at the top-left and a faint metallic grain veil (subtle feTurbulence in soft-light, about 14% opacity).
- Relief comes from gradients plus an feSpecularLighting filter on the emblem: a light highlight and a cast shadow so it reads as embossed metal.

What changes per article (this is the whole point of the series):
- The EMBLEM engraved at the centre: a simple, iconic symbol of what the article is about.
- A short LEGEND in capitals, engraved on the medallion (one or two French words).
- The aria-label.

Keep it restrained and deliberate: one emblem, one legend, no clutter.
PROMPT;

/* ---------- CLI flags ---------------------------------------------------- */

// --force            regenerate every article's medallion
// --force=<slug>     regenerate a single article's medallion
$force     = false;
$forceSlug = null;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--force') {
        $force = true;
    } elseif (str_starts_with($arg, '--force=')) {
        $force     = true;
        $forceSlug = substr($arg, 8);
    } else {
        fwrite(STDERR, "Argument inconnu : $arg (attendu : --force ou --force=<slug>)\n");
        exit(1);
    }
}

/* ---------- local secrets ------------------------------------------------ */

// Load KEY=value lines from .env.local (gitignored) into the environment, so a
// plain `php build.php` finds ANTHROPIC_API_KEY without exporting it by hand.
// A value already in the environment wins, so `export` still overrides the file.
$envFile = ROOT . '/.env.local';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . trim($value, " \t\"'"));
        }
    }
}

/* ---------- tiny helpers ------------------------------------------------- */

function tpl(string $name): string
{
    $path = TEMPLATES_DIR . "/$name.html";
    $html = file_get_contents($path);
    if ($html === false) {
        fwrite(STDERR, "Template introuvable : $path\n");
        exit(1);
    }

    return $html;
}

/** @param array<string, string> $vars */
function render(string $template, array $vars): string
{
    foreach ($vars as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }

    return $template;
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function dateHuman(DateTimeImmutable $d): string
{
    return (int) $d->format('j') . ' ' . MONTHS_FR[(int) $d->format('n')] . ' ' . $d->format('Y');
}

function dateShort(DateTimeImmutable $d): string
{
    return (int) $d->format('j') . ' ' . MONTHS_FR[(int) $d->format('n')];
}

function readingTime(string $markdown): string
{
    preg_match_all('/\p{L}+/u', $markdown, $m);
    $minutes = max(1, (int) round(count($m[0]) / 200));

    return $minutes . ' min';
}

/**
 * The inner markup of a thumbnail: the article's SVG when it has one, a serif
 * monogram otherwise (so a post without illustration still looks deliberate).
 *
 * @param array<string, mixed> $post
 */
function thumb(array $post): string
{
    if ($post['thumbnail'] !== '') {
        return '<img src="/' . esc($post['thumbnail']) . '" alt="" width="400" height="300" loading="lazy">';
    }

    $initial = mb_strtoupper(mb_substr($post['title'], 0, 1));

    return '<span class="journal-thumb-mono">' . esc($initial) . '</span>';
}

/**
 * Resolve an article's medallion, generating it on first build (or on --force).
 * The SVG is a committed static asset: once it exists we reuse it, so a normal
 * build makes no network call. Returns the relative path, or '' when there's no
 * art and no way to make one (no key): thumb() then falls back to a monogram.
 *
 * @param array<string, string> $meta
 */
function ensureThumbnail(string $slug, array $meta, string $body, bool $regenerate): string
{
    $rel = "assets/journal/$slug.svg";
    $abs = ROOT . '/' . $rel;

    if (is_file($abs) && !$regenerate) {
        return $rel;
    }

    $apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
    if ($apiKey === '') {
        if (is_file($abs)) {
            return $rel; // keep the committed art; nothing to regenerate without a key
        }
        fwrite(STDERR, "⚠ « {$meta['title']} » : pas d'illustration (ANTHROPIC_API_KEY absente), monogramme utilisé.\n");

        return '';
    }

    fwrite(STDERR, "→ Génération de l'illustration pour « {$meta['title']} »…\n");
    $svg = generateMedallionSvg($apiKey, $meta, $body);
    @mkdir(dirname($abs), 0o755, true);
    file_put_contents($abs, $svg);
    fwrite(STDERR, "  ✓ $rel\n");

    return $rel;
}

/**
 * Ask Claude for one medallion SVG following MEDALLION_BRIEF. Returns the SVG
 * string; exits with a clear message on any API or parsing failure (a broken
 * build is better than committing a half-generated asset).
 *
 * @param array<string, string> $meta
 */
function generateMedallionSvg(string $apiKey, array $meta, string $body): string
{
    $hint = $meta['illustration'] ?? '';
    $user = "Article title: {$meta['title']}\n"
          . "Description: {$meta['description']}\n"
          . ($hint !== '' ? "Illustration hint (follow it closely): $hint\n" : '')
          . "\nOpening of the article, for context:\n"
          . mb_substr(trim($body), 0, 800)
          . "\n\nDraw the medallion for this article.";

    $payload = json_encode([
        'model'      => IMAGE_MODEL,
        'max_tokens' => 16000,
        'system'     => MEDALLION_BRIEF,
        'messages'   => [['role' => 'user', 'content' => $user]],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init(ANTHROPIC_API);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT    => 300,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);

    if ($raw === false) {
        fwrite(STDERR, "Appel API échoué : $err\n");
        exit(1);
    }

    $data = json_decode((string) $raw, true);
    if ($code !== 200) {
        $msg = $data['error']['message'] ?? (string) $raw;
        fwrite(STDERR, "API $code : $msg\n");
        exit(1);
    }
    if (($data['stop_reason'] ?? '') === 'refusal') {
        fwrite(STDERR, "Génération refusée par le modèle.\n");
        exit(1);
    }
    if (($data['stop_reason'] ?? '') === 'max_tokens') {
        fwrite(STDERR, "SVG tronqué (max_tokens atteint) : augmente max_tokens dans build.php.\n");
        exit(1);
    }

    $text = '';
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $text .= $block['text'];
        }
    }

    // Keep only the <svg>…</svg>, in case the model wrapped it in a fence or prose.
    if (preg_match('/<svg\b.*<\/svg>/s', $text, $m) !== 1) {
        fwrite(STDERR, "Réponse sans SVG exploitable :\n" . trim($text) . "\n");
        exit(1);
    }

    return $m[0] . "\n";
}

/* ---------- 1. load posts ------------------------------------------------ */

$converter = new CommonMarkConverter([
    'html_input'         => 'allow',  // content is mine, raw HTML is welcome
    'allow_unsafe_links' => false,
]);

$files = glob(POSTS_DIR . '/*.md') ?: [];
if ($files === []) {
    fwrite(STDERR, "Aucun article dans " . POSTS_DIR . "\n");
    exit(1);
}

$posts = [];
foreach ($files as $file) {
    $raw = (string) file_get_contents($file);

    if (!preg_match('/^---\R(.*?)\R---\R(.*)$/su', $raw, $parts)) {
        fwrite(STDERR, "Frontmatter manquant ou mal formé : $file\n");
        exit(1);
    }

    $meta = [];
    foreach (preg_split('/\R/', trim($parts[1])) as $line) {
        if (preg_match('/^([a-z]+)\s*:\s*(.*)$/i', $line, $kv)) {
            $meta[strtolower($kv[1])] = trim($kv[2]);
        }
    }

    foreach (['title', 'date', 'description'] as $required) {
        if (empty($meta[$required])) {
            fwrite(STDERR, "Frontmatter incomplet ($required) : $file\n");
            exit(1);
        }
    }

    $body = trim($parts[2]);
    $slug = preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', basename($file, '.md'));
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $meta['date']);
    if ($date === false) {
        fwrite(STDERR, "Date invalide (attendu Y-m-d) : $file\n");
        exit(1);
    }

    // Thumbnail: an explicit non-conventional path is respected as-is; otherwise
    // we use (and generate on first build) the conventional assets/journal/<slug>.svg.
    $thumb      = $meta['thumbnail'] ?? '';
    $defaultRel = "assets/journal/$slug.svg";
    if ($thumb === '' || $thumb === $defaultRel) {
        $regen = $force && ($forceSlug === null || $forceSlug === $slug);
        $thumb = ensureThumbnail($slug, $meta, $body, $regen);
    }

    $posts[] = [
        'slug'        => $slug,
        'title'       => $meta['title'],
        'description' => $meta['description'],
        'date'        => $date,
        'thumbnail'   => $thumb,
        'reading'     => readingTime($body),
        'bodyHtml'    => $converter->convert($body)->getContent(),
    ];
}

// Most recent first; stable tiebreak on slug for reproducible builds.
usort($posts, fn (array $a, array $b): int =>
    ($b['date'] <=> $a['date']) ?: strcmp($a['slug'], $b['slug']));

/* ---------- 2. one page per article ------------------------------------- */

$layout      = tpl('layout');
$articleTpl  = tpl('article');

foreach ($posts as $post) {
    $url = BASE_URL . '/journal/' . $post['slug'] . '/';

    $jsonLd = json_encode([
        '@context'      => 'https://schema.org',
        '@type'         => 'BlogPosting',
        'headline'      => $post['title'],
        'description'   => $post['description'],
        'datePublished' => $post['date']->format('Y-m-d'),
        'url'           => $url,
        'author'        => ['@type' => 'Person', 'name' => 'Thibault Lafaurie', 'url' => BASE_URL . '/'],
        'publisher'     => ['@type' => 'Person', 'name' => 'Thibault Lafaurie'],
        'inLanguage'    => 'fr-FR',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $hero = $post['thumbnail'] !== ''
        ? '      <figure class="article-hero"><img src="/' . esc($post['thumbnail']) . '" alt="" width="400" height="300"></figure>' . "\n"
        : '';

    $main = render($articleTpl, [
        'TITLE'        => esc($post['title']),
        'DATE_HUMAN'   => dateHuman($post['date']),
        'READING_TIME' => $post['reading'] . ' de lecture',
        'HERO'         => $hero,
        'BODY'         => $post['bodyHtml'],
    ]);

    $page = render($layout, [
        'TITLE'       => esc($post['title']) . ' - Journal · Thibault Lafaurie',
        'DESCRIPTION' => esc($post['description']),
        'CANONICAL'   => $url,
        'OG_TYPE'     => 'article',
        'CHROME_URL'  => 'https://labault.dev/journal/' . $post['slug'],
        'HEAD_EXTRA'  => "\n<!-- JSON-LD : BlogPosting -->\n<script type=\"application/ld+json\">\n$jsonLd\n</script>",
        'MAIN'        => $main,
    ]);

    $dir = ROOT . '/journal/' . $post['slug'];
    @mkdir($dir, 0o755, true);
    file_put_contents($dir . '/index.html', $page);
}

/* ---------- 3. journal index (grouped by year) -------------------------- */

$rowTpl  = tpl('row');
$yearTpl = tpl('year');

$byYear = [];
foreach ($posts as $post) {
    $byYear[$post['date']->format('Y')][] = $post;
}

$groups = '';
foreach ($byYear as $year => $yearPosts) {
    $entries = '';
    foreach ($yearPosts as $post) {
        $entries .= render($rowTpl, [
            'SLUG'        => $post['slug'],
            'THUMB'       => thumb($post),
            'DATE'        => dateShort($post['date']),
            'READING'     => $post['reading'],
            'TITLE'       => esc($post['title']),
            'DESCRIPTION' => esc($post['description']),
        ]);
    }
    $groups .= render($yearTpl, ['YEAR' => (string) $year, 'ENTRIES' => $entries]);
}

$indexMain = render(tpl('list'), ['GROUPS' => $groups]);
$indexPage = render($layout, [
    'TITLE'       => 'Journal - Thibault Lafaurie',
    'DESCRIPTION' => 'Notes de prod : migrations, legacy, infra et les pièges du terrain. Le journal de Thibault Lafaurie, développeur backend PHP / Symfony.',
    'CANONICAL'   => BASE_URL . '/journal/',
    'OG_TYPE'     => 'website',
    'CHROME_URL'  => 'https://labault.dev/journal',
    'HEAD_EXTRA'  => '',
    'MAIN'        => $indexMain,
]);

@mkdir(ROOT . '/journal', 0o755, true);
file_put_contents(ROOT . '/journal/index.html', $indexPage);

/* ---------- 4. inject the latest teasers into the home page ------------- */

$cardTpl = tpl('card');
$cards   = '';
foreach (array_slice($posts, 0, TEASER_COUNT) as $post) {
    $cards .= render($cardTpl, [
        'SLUG'        => $post['slug'],
        'THUMB'       => thumb($post),
        'DATE'        => dateHuman($post['date']),
        'READING'     => $post['reading'],
        'TITLE'       => esc($post['title']),
        'DESCRIPTION' => esc($post['description']),
    ]);
}

$total = count($posts);
$cta   = $total > 1 ? "Voir les $total articles →" : "Voir l'article →";

$home  = (string) file_get_contents(ROOT . '/index.html');
$block = "<!-- JOURNAL:START -->\n"
       . "        <div class=\"journal-cards\">\n"
       . $cards
       . "        </div>\n"
       . "        <a class=\"journal-teaser-all\" href=\"/journal/\">$cta</a>\n"
       . "        <!-- JOURNAL:END -->";

$home = preg_replace(
    '/<!-- JOURNAL:START -->.*?<!-- JOURNAL:END -->/s',
    $block,
    $home,
    1,
    $injected
);

if ($injected === 0) {
    fwrite(STDERR, "Marqueurs JOURNAL:START/END absents de index.html : teasers non injectés.\n");
    exit(1);
}
file_put_contents(ROOT . '/index.html', $home);

/* ---------- 5. sitemap --------------------------------------------------- */

$latest = $posts[0]['date']->format('Y-m-d');
$urls   = [];
$urls[] = ['loc' => BASE_URL . '/',         'lastmod' => $latest, 'changefreq' => 'monthly', 'priority' => '1.0'];
$urls[] = ['loc' => BASE_URL . '/journal/', 'lastmod' => $latest, 'changefreq' => 'weekly',  'priority' => '0.8'];
foreach ($posts as $post) {
    $urls[] = [
        'loc'        => BASE_URL . '/journal/' . $post['slug'] . '/',
        'lastmod'    => $post['date']->format('Y-m-d'),
        'changefreq' => 'yearly',
        'priority'   => '0.6',
    ];
}

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
     . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $u) {
    $xml .= "  <url>\n"
          . "    <loc>{$u['loc']}</loc>\n"
          . "    <lastmod>{$u['lastmod']}</lastmod>\n"
          . "    <changefreq>{$u['changefreq']}</changefreq>\n"
          . "    <priority>{$u['priority']}</priority>\n"
          . "  </url>\n";
}
$xml .= "</urlset>\n";
file_put_contents(ROOT . '/sitemap.xml', $xml);

/* ---------- done --------------------------------------------------------- */

$count = count($posts);
echo "✓ $count article" . ($count > 1 ? 's' : '') . " généré" . ($count > 1 ? 's' : '') . "\n";
echo "✓ journal/index.html\n";
echo "✓ " . TEASER_COUNT . " teaser(s) injecté(s) dans index.html\n";
echo "✓ sitemap.xml (" . count($urls) . " URLs)\n";
