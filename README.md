# labault.dev

My personal portfolio. One page, two files, no build step.

**Live:** [labault.dev](https://labault.dev)

## The idea

A single static page, hand-written in HTML and CSS. No framework, no bundler,
no npm install that downloads half the internet. The whole thing is a fake
browser window — a portfolio dressed up as the browser it runs in. Yes, it's a
little meta. That's the point.

The page is in French (it's aimed at French clients); the code, comments and
this README are in English. If you opened DevTools to read the source, the
comments left a few things for you to find.

## Stack

- **HTML** — semantic, accessible, zero JavaScript
- **CSS** — custom properties, grid, a couple of keyframe animations
- **Fonts** — [Inter](https://rsms.me/inter/) + DM Serif Display, via Google Fonts
- **Tech logos** — [devicon](https://devicon.dev/) over jsDelivr

That's it. The only network dependencies are the fonts and the icon CDN.

## Structure

```
.
├── index.html      # the whole page
├── style.css       # the whole style
├── robots.txt      # crawlers welcome
├── sitemap.xml     # one URL, but it's polite to ask
├── assets/         # project illustrations & tech logos
├── favicon.png
├── portrait-*.jpg  # the two photos behind the hover swap
└── CV_*.pdf
```

## Running it locally

It's static, so anything that serves files will do:

```bash
python3 -m http.server 8000
# then open http://localhost:8000
```

Or just open `index.html` in a browser. The Google Fonts and CDN icons need a
connection, the rest works offline.

## Deploying

Copy the files to any web root. It currently lives on a VPS behind Traefik with
Let's Encrypt — no special server config required, it's plain static hosting.

## License

The code is free to read and learn from. The content, photos and CV are mine —
please don't reuse those. See [LICENSE](LICENSE) for the details.

— Thibault Lafaurie · [contact@labault.dev](mailto:contact@labault.dev)
