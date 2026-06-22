# labault.dev

My personal portfolio. One page, two files, no build step, no regrets.

**Live:** [labault.dev](https://labault.dev)

## Wait, why is this public?

Good question. Not so you can `git clone` it and ship it as your own (please
don't - see the [LICENSE](LICENSE), and also, have some self-respect). It's
public because the portfolio is a window, and this repo is the same window with
the curtains pulled all the way back. If a recruiter or a client wants to see
how I write code when nobody's forcing me to, it's all right here: the commits,
the comments, the questionable decisions I stand by.

Think of it as a second showroom. The site is the polished front. This is the
workshop, with the sawdust left in on purpose.

## The idea

A single static page, hand-written in HTML and CSS. No framework, no bundler,
no `npm install` that quietly downloads half of the internet and a node_modules
folder you could see from space. Just markup and a stylesheet, the way the
ancients intended.

The whole thing is dressed up as a fake browser window - a portfolio rendered
inside the browser it's already running in. Yes, it's a little meta. No, I will
not be apologizing for it.

The page itself is in French (it's aimed at French clients). The code, the
comments and this README are in English - because that's where I keep my dev
voice, and because future-me reads diffs in English. If you opened DevTools
expecting boilerplate, the comments left you a few things to find.

## Philosophy, or: why no JavaScript

The site ships **zero** JavaScript. The photo swap on hover? CSS. The blinking
cursor, the pulsing "available" dot, the slow Ken Burns zoom? All CSS. The old
dev proverb says *if it works, don't touch it* - turns out a lot of things work
without a 200 KB runtime.

There's a deliberate bit of irony baked in, too: the page lists "HTML / CSS" as
a proven skill... and the proof is the page you're reading the source of. Call
it *mise en abyme*. Call it a bold marketing move. (It's both.)

## Stack

For a "no-dependency" site it has surprisingly strong opinions:

- **HTML** - semantic, accessible, and stubbornly framework-free
- **CSS** - custom properties, grid, a handful of keyframe animations, colons aligned in columns because I have a type
- **Fonts** - [Inter](https://rsms.me/inter/) for the body, DM Serif Display for the drama, both via Google Fonts
- **Tech logos** - [devicon](https://devicon.dev/) served over jsDelivr

The only things that touch the network are the fonts and the icon CDN. Pull the
plug and the page still mostly stands - it just dresses down a little.

## What's in the box

```
.
├── index.html      # the whole page. yes, all of it.
├── style.css       # the whole style. also all of it.
├── og-image.svg    # the social-share card (editable source)
├── og-image.png    # ...rasterized, because nobody renders SVG og:images
├── robots.txt      # crawlers welcome, mind the rugs
├── sitemap.xml     # one URL, but it's polite to announce yourself
├── deploy.sh       # ships it, then asks the site if it's still breathing
├── assets/         # project illustrations & tech logos
├── favicon.png
├── portrait-*.jpg  # the two photos behind the hover swap
└── CV_*.pdf        # the formal version of all this
```

Two HTML/CSS files do the heavy lifting. Everything else is packaging.

## Deploying

Copy the files to a web root. Done. It currently lives on a VPS behind Traefik
with Let's Encrypt doing the TLS - no build, no runtime, no "works on my
machine," because there's nothing to break between my machine and the server.
`deploy.sh` pushes the files and then politely curls the live URL to confirm the
site is actually answering, instead of trusting that "the container is running"
means "the page loads." It has, on occasion, not meant that.

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
