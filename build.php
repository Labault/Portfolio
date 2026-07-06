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
 * A cover is a fixed bronze-medallion frame with a per-article emblem swapped in.
 * MEDALLION_TEMPLATE is that frame, lifted verbatim from the hand-made originals;
 * only three holes change per article: {{ARIA}}, {{EMBLEM}} and {{LEGEND}}. The
 * model is asked for exactly those three (never the frame), so every generated
 * cover matches the series by construction. Edit the template to move the whole
 * series at once; edit MEDALLION_BRIEF to change what the model draws in the hole.
 */
const MEDALLION_TEMPLATE = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" role="img" aria-label="{{ARIA}}">
  <defs>
    <radialGradient id="bg" cx="50%" cy="38%" r="80%">
      <stop offset="0%" stop-color="#21375A"/>
      <stop offset="56%" stop-color="#13223B"/>
      <stop offset="100%" stop-color="#091221"/>
    </radialGradient>
    <radialGradient id="halo" cx="50%" cy="42%" r="50%">
      <stop offset="0%" stop-color="#E0A560" stop-opacity="0.22"/>
      <stop offset="55%" stop-color="#C9824A" stop-opacity="0.07"/>
      <stop offset="100%" stop-color="#C9824A" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="coin" cx="37%" cy="29%" r="82%">
      <stop offset="0%"   stop-color="#FCE9CE"/>
      <stop offset="16%"  stop-color="#F1CB97"/>
      <stop offset="34%"  stop-color="#DDA363"/>
      <stop offset="52%"  stop-color="#C07F45"/>
      <stop offset="70%"  stop-color="#9E5E32"/>
      <stop offset="86%"  stop-color="#7C4524"/>
      <stop offset="100%" stop-color="#552E15"/>
    </radialGradient>
    <linearGradient id="rim" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%"   stop-color="#FDE7C6"/>
      <stop offset="35%"  stop-color="#C77E47"/>
      <stop offset="70%"  stop-color="#7B431F"/>
      <stop offset="100%" stop-color="#41220D"/>
    </linearGradient>
    <linearGradient id="field" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%"   stop-color="#5A3017"/>
      <stop offset="24%"  stop-color="#7C4824"/>
      <stop offset="58%"  stop-color="#A86A3C"/>
      <stop offset="100%" stop-color="#C88C56"/>
    </linearGradient>
    <radialGradient id="glint" cx="34%" cy="26%" r="46%">
      <stop offset="0%"   stop-color="#FFFBF2" stop-opacity="0.6"/>
      <stop offset="60%"  stop-color="#FFFBF2" stop-opacity="0.12"/>
      <stop offset="100%" stop-color="#FFFBF2" stop-opacity="0"/>
    </radialGradient>
    <clipPath id="face"><circle cx="200" cy="148" r="82"/></clipPath>
    <filter id="grain" x="0" y="0" width="100%" height="100%">
      <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="21" result="n"/>
      <feColorMatrix in="n" type="saturate" values="0"/>
    </filter>
    <filter id="emboss" x="-30%" y="-30%" width="160%" height="160%">
      <feGaussianBlur in="SourceAlpha" stdDeviation="1.3" result="blur"/>
      <feSpecularLighting in="blur" surfaceScale="3.2" specularConstant="0.95" specularExponent="20" lighting-color="#fff3e0" result="spec">
        <feDistantLight azimuth="230" elevation="56"/>
      </feSpecularLighting>
      <feComposite in="spec" in2="SourceAlpha" operator="in" result="specC"/>
      <feOffset in="SourceAlpha" dx="1.1" dy="1.4" result="off"/>
      <feGaussianBlur in="off" stdDeviation="1" result="offb"/>
      <feFlood flood-color="#371D0C" flood-opacity="0.75" result="sh"/>
      <feComposite in="sh" in2="offb" operator="in" result="shadow"/>
      <feMerge>
        <feMergeNode in="shadow"/>
        <feMergeNode in="SourceGraphic"/>
        <feMergeNode in="specC"/>
      </feMerge>
    </filter>
    <filter id="soft" x="-50%" y="-50%" width="200%" height="200%">
      <feGaussianBlur stdDeviation="7"/>
    </filter>
  </defs>

  <rect width="400" height="300" fill="url(#bg)"/>
  <rect width="400" height="300" fill="url(#halo)"/>

  <g fill="none" stroke="#2C4263" stroke-width="0.8" opacity="0.5">
    <circle cx="200" cy="148" r="109"/>
    <circle cx="200" cy="148" r="121"/>
  </g>

  <circle cx="200" cy="157" r="93" fill="#04101C" opacity="0.55" filter="url(#soft)"/>

  <circle cx="200" cy="148" r="95" fill="url(#rim)"/>
  <circle cx="200" cy="148" r="91" fill="none" stroke="#FCE0BC" stroke-width="4.5" stroke-dasharray="1.2 4.6" opacity="0.55"/>
  <circle cx="200" cy="148" r="86" fill="#3F2210"/>
  <circle cx="200" cy="148" r="86" fill="none" stroke="#3E6B52" stroke-width="1.4" opacity="0.35"/>

  <circle cx="200" cy="148" r="82" fill="url(#coin)"/>

  <g clip-path="url(#face)">
    <rect x="118" y="66" width="164" height="164" filter="url(#grain)" opacity="0.14" style="mix-blend-mode:soft-light"/>
    <circle cx="200" cy="148" r="82" fill="url(#glint)"/>
    <g fill="none">
      <circle cx="200" cy="148" r="78" stroke="#FCE3C0" stroke-width="0.5" opacity="0.4"/>
      <circle cx="200" cy="148" r="76" stroke="#4A2710" stroke-width="0.6" opacity="0.4"/>
      <circle cx="200" cy="148" r="72" stroke="#4A2710" stroke-width="0.6" opacity="0.35"/>
      <circle cx="200" cy="148" r="68" stroke="#FCE3C0" stroke-width="0.5" opacity="0.3"/>
    </g>
  </g>

  <circle cx="200" cy="148" r="62" fill="url(#field)"/>
  <circle cx="200" cy="148" r="62" fill="none" stroke="#3A1F0E" stroke-width="2.4" opacity="0.55"/>
  <circle cx="200" cy="148" r="60" fill="none" stroke="#3E6B52" stroke-width="1.6" opacity="0.4"/>
  <circle cx="200" cy="148" r="58.5" fill="none" stroke="#F3CE9E" stroke-width="0.7" opacity="0.25"/>
  <g fill="none" stroke="#6E3C1C" opacity="0.18">
    <circle cx="200" cy="148" r="50"/>
    <circle cx="200" cy="148" r="40"/>
  </g>

  <!-- Emblème gravé en relief, dans le bronze de la pièce -->
  <g filter="url(#emboss)">
{{EMBLEM}}
  </g>

  <text x="200" y="285" text-anchor="middle" font-family="Inter, system-ui, sans-serif" font-size="11" letter-spacing="0.33em" fill="#D29A66">{{LEGEND}}</text>
</svg>
SVG;

/*
 * What the model fills into the three holes above. It never sees or redraws the
 * frame: it returns the emblem markup, the legend word and the aria-label, as JSON.
 */
const MEDALLION_BRIEF = <<<'PROMPT'
You design the central emblem and the one-word legend for a bronze-medallion cover
in an existing blog series. The medallion frame (night-blue field, bronze coin,
bevelled rim, recessed centre, lighting) is FIXED and identical on every cover:
you do NOT draw it. You return three things as JSON: emblem, legend, aria.

emblem — the one thing that changes per article, an SVG fragment:
- A single, simple, iconic symbol of what the article is about. Clean pictogram,
  one idea, no text inside it, no clutter.
- It is ENGRAVED INTO THE BRONZE: every shape MUST use fill="url(#coin)" or
  stroke="url(#coin)" (the coin's own bronze gradient). Never any other colour, no
  grey, no black, no flat fill. A raised-metal emboss filter is applied around it
  for you, so plain bronze line-art comes out as relief. A shape in any other
  colour would look wrong.
- Centred on (200,148), kept inside a 70x70 box (x 166 to 234, y 114 to 182).
- Favour strokes of width 3 to 4.5 with stroke-linecap="round", plus a few small
  filled dots or shapes. Think engraved emblem, not detailed drawing.
- Return the inner markup only: paths, lines, circles, groups. No wrapping <g>, no
  filter attribute, no <svg>, no <script>. Those are added around your fragment.

legend — one short French word in CAPITALS (like INFRA, NIVEAU, MIGRATION). One
word, occasionally two very short ones. No punctuation.

aria — one French sentence following the house pattern:
"Médaillon de bronze patiné, gravé en relief : <what the emblem shows>. <the point of the article>."
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
 * Ask Claude only for the three per-article holes (emblem markup, legend, aria)
 * and drop them into MEDALLION_TEMPLATE, so the frame stays byte-identical to the
 * hand-made covers. Exits with a clear message on any API or parsing failure (a
 * broken build beats committing a half-generated asset).
 *
 * @param array<string, string> $meta
 */
function generateMedallionSvg(string $apiKey, array $meta, string $body): string
{
    $hint = $meta['illustration'] ?? '';
    $user = "Article title: {$meta['title']}\n"
          . "Description: {$meta['description']}\n"
          . ($hint !== '' ? "Emblem hint (follow it closely): $hint\n" : '')
          . "\nOpening of the article, for context:\n"
          . mb_substr(trim($body), 0, 800)
          . "\n\nDesign the emblem, legend and aria-label for this article.";

    $payload = json_encode([
        'model'         => IMAGE_MODEL,
        'max_tokens'    => 4000,
        'system'        => MEDALLION_BRIEF,
        'messages'      => [['role' => 'user', 'content' => $user]],
        'output_config' => [
            'format' => [
                'type'   => 'json_schema',
                'schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'emblem' => ['type' => 'string'],
                        'legend' => ['type' => 'string'],
                        'aria'   => ['type' => 'string'],
                    ],
                    'required'             => ['emblem', 'legend', 'aria'],
                    'additionalProperties' => false,
                ],
            ],
        ],
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
        fwrite(STDERR, "Réponse tronquée (max_tokens atteint) : augmente max_tokens dans build.php.\n");
        exit(1);
    }

    $text = '';
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $text .= $block['text'];
        }
    }

    $parts = json_decode(trim($text), true);
    if (!is_array($parts) || !isset($parts['emblem'], $parts['legend'], $parts['aria'])) {
        fwrite(STDERR, "Réponse JSON inattendue :\n" . trim($text) . "\n");
        exit(1);
    }

    $emblem = trim((string) $parts['emblem']);
    // Guard against a wrapping <g ...emboss...> so the template's filter isn't doubled.
    if (preg_match('#^<g\b[^>]*emboss[^>]*>(.*)</g>\s*$#s', $emblem, $mm) === 1) {
        $emblem = trim($mm[1]);
    }
    if ($emblem === '' || stripos($emblem, '<script') !== false) {
        fwrite(STDERR, "Emblème vide ou non sûr.\n");
        exit(1);
    }
    if (stripos($emblem, 'url(#coin)') === false) {
        fwrite(STDERR, "⚠ L'emblème n'utilise pas le bronze url(#coin) : le rendu risque de jurer.\n");
    }

    return rtrim(strtr(MEDALLION_TEMPLATE, [
        '{{ARIA}}'   => htmlspecialchars((string) $parts['aria'], ENT_COMPAT, 'UTF-8'),
        '{{EMBLEM}}' => $emblem,
        '{{LEGEND}}' => htmlspecialchars(mb_strtoupper(trim((string) $parts['legend'])), ENT_COMPAT, 'UTF-8'),
    ])) . "\n";
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
