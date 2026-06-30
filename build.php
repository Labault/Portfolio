<?php

declare(strict_types=1);

/*
 * build.php — the whole "blog engine", such as it is.
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
        return '<img src="/' . esc($post['thumbnail']) . '" alt="" loading="lazy">';
    }

    $initial = mb_strtoupper(mb_substr($post['title'], 0, 1));

    return '<span class="journal-thumb-mono">' . esc($initial) . '</span>';
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

    $posts[] = [
        'slug'        => $slug,
        'title'       => $meta['title'],
        'description' => $meta['description'],
        'date'        => $date,
        'thumbnail'   => $meta['thumbnail'] ?? '',
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
        ? '      <figure class="article-hero"><img src="/' . esc($post['thumbnail']) . '" alt=""></figure>' . "\n"
        : '';

    $main = render($articleTpl, [
        'TITLE'        => esc($post['title']),
        'DATE_HUMAN'   => dateHuman($post['date']),
        'READING_TIME' => $post['reading'] . ' de lecture',
        'HERO'         => $hero,
        'BODY'         => $post['bodyHtml'],
    ]);

    $page = render($layout, [
        'TITLE'       => esc($post['title']) . ' — Journal · Thibault Lafaurie',
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
    'TITLE'       => 'Journal — Thibault Lafaurie',
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

$home  = (string) file_get_contents(ROOT . '/index.html');
$block = "<!-- JOURNAL:START -->\n"
       . "        <div class=\"journal-cards\">\n"
       . $cards
       . "        </div>\n"
       . "        <!-- JOURNAL:END -->";

$home = preg_replace(
    '/<!-- JOURNAL:START -->.*?<!-- JOURNAL:END -->/s',
    $block,
    $home,
    1,
    $injected
);

if ($injected === 0) {
    fwrite(STDERR, "Marqueurs JOURNAL:START/END absents de index.html — teasers non injectés.\n");
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
