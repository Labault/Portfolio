---
title: No PaaS, no Friday
date: 2026-06-16
description: Pas de PaaS, pas de Kubernetes : un seul VPS que je gère de bout en bout. La condition pour que ça tienne, c'est que déployer soit assez ennuyeux pour le faire un vendredi à 17h.
thumbnail: assets/journal/no-paas-no-friday.svg
---

Tu as une app à mettre en ligne. Le réflexe par défaut aujourd'hui : un PaaS qui déploie en un clic, ou, si tu as vraiment envie de souffrir, un cluster Kubernetes pour faire tourner trois conteneurs. Moi, je mets ça sur un seul VPS que je gère de bout en bout. Et non, ce n'est pas de la nostalgie.

## Le vrai coût d'un PaaS, ce n'est pas la facture

Un PaaS, c'est confortable jusqu'au jour où ça casse. Tant que tout va bien tu pushes, ça déploie, tu ne penses à rien. Le problème c'est exactement ça : tu ne penses à rien, donc tu ne sais plus où tourne ton app. Le jour où une requête part en vrille, où un build échoue sans raison claire, où une limite que tu ne connaissais pas te tombe dessus, tu débugges la plateforme de quelqu'un d'autre, à travers une couche d'abstraction que tu n'as pas écrite et que tu ne peux pas lire.

Le coût d'un PaaS, ce ne sont pas les euros par mois. C'est la compréhension que tu abandonnes. Tu loues un truc qui marche, et tu pries pour qu'il continue.

## Ce que je mets à la place

Un VPS. Un reverse-proxy Caddy devant. Un déploiement déclenché par un webhook signé : je push, un hook vérifie la signature HMAC, tire le code, reconstruit, bascule. Et bien sûr des sauvegardes automatisées et du monitoring, parce que s'héberger sans filet, ce n'est pas s'héberger, c'est jouer.

Rien de magique là-dedans, et c'est justement le but : chaque morceau, je peux le lire, le raisonner, le reproduire. Quand ça casse, je sais où regarder, parce que c'est moi qui l'ai posé. Pas de ticket de support à ouvrir, pas de page de statut à rafraîchir en espérant que ce n'est pas de mon côté.

## "No Friday", c'est la condition, pas la blague

S'héberger soi-même n'est responsable qu'à une condition : que déployer soit ennuyeux. Si ta mise en prod te fait peur, tu n'as pas un problème d'hébergement, tu as un problème de déploiement, et c'est lui que tu règles d'abord.

Chez moi, déployer c'est un push qui déclenche un webhook signé, reproductible, réversible. Tellement sans suspense que je peux le faire un vendredi à 17h sans transpirer. C'est ça qui rend le self-hosting tenable : pas le courage, l'absence de surprise. "No Friday" n'est pas une superstition, c'est le symptôme inverse. Le jour où déployer un vendredi ne change rien, c'est que tu as fait le travail en amont.

## Le piège qu'on n'oublie qu'une fois

Tout n'est pas lisse pour autant. La config du reverse-proxy, par exemple, se gère par le flux de déploiement : en local, puis commit, puis deploy. Jamais à la main sur le serveur. Le jour où tu édites le fichier directement sur la machine et que tu fais un reload en pensant que ça suffit, tu découvres que non, le changement n'est pas pris, et tu perds une demi-heure à comprendre pourquoi. La leçon : sur une infra que tu gères toi-même, la source de vérité c'est le dépôt, pas le serveur. Tu bidouilles le serveur à la main, tu ouvres la porte à l'état qui dérive. Et l'état qui dérive, c'est exactement l'inverse de ce que tu cherchais en t'hébergeant toi-même.

## Pour qui c'est, pour qui ça ne l'est pas

Je ne dis pas que le PaaS est une erreur. Pour une équipe qui veut zéro ops et qui a le budget, c'est un arbitrage légitime, et il m'arrive de faire l'exception quand le contexte le justifie. Ce que je dis, c'est que pour la plupart des produits, à l'échelle réelle qu'ils ont et pas celle qu'ils fantasment, un VPS bien tenu fait le travail, coûte moins cher, et te rend quelque chose que le PaaS te prend : tu sais comment ton app tourne.

No PaaS, no Kubernetes, no Friday. Pas un slogan anti-cloud. Une préférence pour les choses que je comprends.
