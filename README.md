# labault.dev

My personal portfolio. Static HTML, zero JavaScript shipped, and the only
build tool involved is one I wrote myself.

**Live:** [labault.dev](https://labault.dev)

## Wait, why is this public?

Good question. Not so you can `git clone` it and ship it as your own (please
don't - see the [LICENSE](LICENSE), and also, have some self-respect). It's
public because the portfolio is a window, and this repo is the same window with
the curtains pulled all the way back: the commits, the structure, and the source
sitting right behind the polished front.

## The idea

A static site, built with plain HTML and CSS. No framework, no bundler, no
`npm install` that quietly downloads half of the internet and a node_modules
folder you could see from space. Just markup and a stylesheet, the way the
ancients intended.

The whole thing is dressed up as a fake browser window - a portfolio rendered
inside the browser it's already running in. Yes, it's a little meta. No, I will
not be apologizing for it.

The page itself is in French (it's aimed at French clients). The code, the
comments and this README are in English. If you open DevTools expecting
boilerplate, the comments left you a few things to find.

## About that "no build step"

The portfolio started as one page and two files. Then I added a journal, and a
journal that you feed by hand-editing `<article>` tags is a journal you abandon
by the third post. So there's now a build step. I'd rather be honest about it
than pretend.

Here's the part I'm not apologizing for either: it runs on *my* machine, never
on the server. It's a ~250-line PHP script I wrote, not a toolchain I rent. Its
single dependency is a Markdown parser, because writing my own would be a trap,
not a flex. And what ships is still plain HTML and CSS. **No build step in
production, and the one build tool is mine.** I like owning the whole chain,
from the Markdown to the VPS.

## Philosophy, or: why no JavaScript

The site ships **zero** JavaScript. The photo swap on hover? CSS. The blinking
cursor, the pulsing "available" dot, the slow Ken Burns zoom? All CSS. The
journal pages? Static HTML, generated once, then just sitting there. The old dev
proverb says *if it works, don't touch it* - turns out a lot of things work
without a 200 KB runtime.

## The journal

The blog lives at [/journal](https://labault.dev/journal/). Each article is one
Markdown file in `journal/_posts/`. `build.php` turns each into its own static
page with a clean URL, draws its cover, builds the year-grouped index, drops the
latest few as teasers on the home page, and refreshes the sitemap. Per-article
`<title>`, meta description, Open Graph and JSON-LD come for free, so every entry
is its own front door for Google.

### Writing a new entry

1. Drop a Markdown file in `journal/_posts/`, named `YYYY-MM-DD-the-slug.md`.
   The date orders the list; the slug becomes the URL (`/journal/the-slug/`).
2. Give it front matter and a body. No `# H1` in the body - the template renders
   the title from the front matter:

   ```markdown
   ---
   title: A title that earns its click
   date: 2026-06-30
   description: One sentence. Doubles as the teaser, the meta description and the OG card.
   ---

   The body, in Markdown. Headings, code, lists, links, the usual.
   ```

3. Build it:

   ```bash
   composer install   # once: restores vendor/ (never deployed)
   php build.php       # generates a cover on first run, needs a key (see below)
   ```

4. Read the result, commit the generated HTML *and* the new cover, push. The
   deploy does the rest.

Two warnings the code already gives you, repeated here for the people who read
READMEs and not comments: don't hand-edit anything between the `JOURNAL:START`
and `JOURNAL:END` markers in `index.html` (the build overwrites it), and don't
edit the generated `journal/**/index.html` files (edit the Markdown instead).

### The covers

Every article wears a bronze-medallion cover, one SVG per post in
`assets/journal/<slug>.svg`. You don't draw it and you don't reference it: on the
first build, if a post has no cover, `build.php` asks Claude for one and writes it
next to the others. After that it's a plain committed asset, served like any other
file. The build only calls out when a cover is missing, so a normal rebuild is
offline and free.

The house style (night-blue field, bronze coin, engraved emblem) lives in one
constant, `MEDALLION_BRIEF`, at the top of `build.php`. Edit it to move the whole
series at once; steer a single cover with an optional `illustration:` line in the
front matter (`illustration: a test tube holding a chip`). Set an explicit
`thumbnail:` and the build leaves it alone. To redraw: `php build.php --force` for
all, `php build.php --force=the-slug` for one.

It reads the key from a gitignored `.env.local` it loads on its own, so `php
build.php` just works:

```bash
# .env.local, never committed
ANTHROPIC_API_KEY=sk-ant-...
```

Prefer an `export ANTHROPIC_API_KEY=...` in `~/.zshrc`? That works too, and wins
over the file.

No key, no drama: existing covers stay put, and a new post without one falls back
to a serif monogram (the build says so). The request goes over plain curl, so this
buys a runtime, not a dependency, the Markdown parser is still the only line in
`composer.json`. And like the rest of the build, it runs on my machine, never the
server.

## Stack

For a "no-dependency" site it has surprisingly strong opinions:

- **HTML** - semantic, accessible, and stubbornly framework-free
- **CSS** - custom properties, grid, a handful of keyframe animations
- **PHP** - `build.php`, the local journal generator, with one dependency:
  [league/commonmark](https://commonmark.thephpleague.com/) for Markdown
- **Fonts** - [Inter](https://rsms.me/inter/) for the body, DM Serif Display for the drama, both via Google Fonts
- **Tech logos** - [devicon](https://devicon.dev/) served over jsDelivr

The only things that touch the network in the browser are the fonts and the icon
CDN. Pull the plug and the page still mostly stands - it just dresses down a
little.

## What's in the box

```
.
├── index.html              # the home page (the journal teaser is injected here)
├── style.css               # the whole style. still one file.
├── build.php               # the journal generator. runs on my machine, never the server.
├── composer.json           # one dependency: league/commonmark
├── journal/
│   ├── _posts/             # articles in Markdown - the source you actually edit
│   ├── _templates/         # the shared shell + the list/article markup
│   ├── index.html          # generated: the journal index
│   └── <slug>/index.html   # generated: one folder per article, one clean URL
├── og-image.svg            # the social-share card (editable source)
├── og-image.png            # ...rasterized, because nobody renders SVG og:images
├── robots.txt              # crawlers welcome, mind the rugs
├── sitemap.xml             # regenerated by build.php
├── deploy.sh               # ships it, then asks the site if it's still breathing
├── assets/                 # project illustrations & tech logos
├── favicon.png
├── portrait-*.jpg          # the two photos behind the hover swap
└── CV_*.pdf                # the formal version of all this
```

`vendor/` isn't in here: it's restored locally with `composer install` and never
deployed. The live site serves the committed HTML, nothing else.

## Deploying

Copy the files to a web root. Done. It currently lives on a VPS behind a shared
Caddy doing TLS and `file_server` - no build, no runtime, no "works on my
machine," because there's nothing to break between my machine and the server.
`deploy.sh` ships the files and then politely curls the live URL to confirm the
site is actually answering, instead of trusting that "the container is running"
means "the page loads." It has, on occasion, not meant that.

Because the whole directory is served statically, the journal *sources* (the
generator, the Markdown, the templates) would otherwise be reachable over HTTP.
They're public in this repo anyway, but there's no reason to serve them. This
lives in the shared Caddy config on the VPS, not in this repo, so here's the
fragment to drop into the `labault.dev` site block:

```caddyfile
# Build-time sources: public in the repo, pointless to serve.
@internal {
    path /build.php
    path /composer.json /composer.lock
    path /journal/_posts/* /journal/_templates/*
}
respond @internal 404
```

## The fine print

This isn't a starter template, and I'd rather you didn't treat it like one. The
code is here to be read, judged, and (ideally) appreciated - not forklifted.
The content, the photos, and the CV are mine; the [LICENSE](LICENSE) spells out
exactly what that means.

---

Made it to the bottom of the README? That's the same energy as reading the HTML
all the way down. People who do that tend to be the people I enjoy working with.
The contact details are one click away on the site - but here, have them anyway:

**Thibault Lafaurie** · backend dev, Clermont-Ferrand
[contact@labault.dev](mailto:contact@labault.dev) · [labault.dev](https://labault.dev) · [github.com/Labault](https://github.com/Labault)
