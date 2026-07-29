---
title: La qualité se décide à la ligne 1, ou jamais
date: 2026-07-29
description: La qualité qu'on repousse ne se rajoute pas plus tard : branchée sur du code déjà écrit, elle génère une baseline que personne ne vide. La seule fenêtre où elle coûte zéro, c'est la ligne 1.
illustration: une roue dentée à cliquet (un rochet et son linguet) qui ne tourne que dans un sens, la qualité qui ne recule jamais
---

Il y a une ligne dans ton README. `TODO: brancher PHPStan.` Elle date du premier commit. Deux cents commits plus tard, elle est toujours là, à la même place, à attendre le jour béni où tu auras "le temps de faire ça proprement". Ce jour n'arrive pas, et le problème n'est pas ta discipline.

La qualité qu'on repousse ne se rajoute pas plus tard. Elle se décide à la ligne 1, ou elle ne se décide jamais. Pas par principe : par mécanique, et cette mécanique a un nom.

## "On l'ajoutera plus tard" est un mensonge mécanique

PHPStan, c'est l'analyseur qui lit ton code sans l'exécuter et te dit où ça casse avant que ça casse. Son niveau 9, le plus strict, ne laisse rien passer. Et c'est un **cliquet** : il ne vaut qu'à une condition, ne jamais pouvoir reculer. Tu montes d'un cran, tu n'en redescends plus.

Sauf que branché sur du code déjà écrit, le niveau 9 te sort des milliers d'erreurs d'un coup. Tu ne vas pas geler trois semaines de features pour les corriger, alors tu fais la seule chose raisonnable : tu génères une baseline. Un fichier qui liste tes péchés du moment et dit à l'outil de les ignorer. Le cliquet ne mord plus que sur le code neuf. L'ancien est gracié.

Et une baseline, personne ne la vide. "Réduire la baseline de douze lignes" n'est le ticket de personne, ne passe jamais avant une feature, n'apparaît sur aucun tableau de bord. Elle ne fond pas. Au mieux elle stagne, au pire elle enfle quand tu ajoutes un module aussi tordu que les précédents. C'est ça, "plus tard".

Ce n'est pas de la dette que tu comptes rembourser. C'est de la dette que tu viens de déclarer légale.

## La fenêtre où ça coûte zéro

Reprends le film à la ligne 1. Il n'y a pas de code. Tu lances PHPStan niveau 9 : il ne trouve rien, parce qu'il n'y a rien. Rector, qui réécrit ton code pour le moderniser, ne réécrit rien. Le CS-Fixer, qui normalise la mise en forme, n'a pas une accolade à replacer. La baseline générée est vide, et le cliquet est armé à fond, pour rien.

C'est toute l'idée de brancher la qualité au bootstrap plutôt que de la promettre. `bootstrap apply --profile symfony`, et le projet naît avec son `phpstan.dist.neon` en niveau 9, son Rector tous sets activés, son CS-Fixer en ruleset Symfony, ses hooks pre-commit qui refusent le commit sale, sa CI qui casse le build au premier écart. Zéro ligne écrite, et tout déjà tenu.

Le coût de la qualité n'est pas fixe, il suit le code. À la ligne 1 il vaut zéro. À la ligne dix mille il vaut trois semaines et une baseline. Ce n'est pas que commencer tôt soit "mieux", c'est que c'est le seul moment où c'est **gratuit**. Chaque ligne suivante naît conforme, parce qu'elle n'a jamais eu le droit de naître autrement.

## Le piège qui existe quand même

Tout n'est pas gratuit pour autant, et le piège vient de la règle la plus stricte du truc : `bootstrap` écrit des fichiers, il n'installe jamais rien. Il dépose ton `phpstan.dist.neon`, mais il ne touche pas à ton `composer.json` : les dépendances, c'est le territoire du projet, pas le sien. Séparation propre, choix assumé.

Le revers, c'est que la config atterrit avant l'outil. Le `.neon` est là, bien rangé, niveau 9 et tout. Tu lances l'analyse : `command not found`. PHPStan n'est pas installé. Le fichier existe, le binaire non, et c'est exactement le genre de bug où tu fixes l'écran cinq minutes en te demandant lequel des deux te ment. Rien n'est cassé. Il te manque la ligne `composer require --dev` que le script t'a affichée en fin de run, et que tu as scrollée sans lire.

Un outil qui pose la config sans poser le binaire te rend un projet cohérent sur le papier et muet à l'exécution. Tu lis la sortie jusqu'au bout, ou tu débugges un fantôme.

## Ce que je ne dis pas

Je ne dis pas que la baseline est une saleté. Sur une migration legacy, une base que tu n'as pas écrite et qui traîne des dizaines de milliers de lignes, tu n'as pas de ligne 1. Tu as la ligne N, et le niveau 9 d'entrée te dresserait un mur d'erreurs infranchissable. Là, la baseline n'est pas une amnistie, c'est un point d'entrée : tu gèles l'existant, tu tiens le neuf, tu grattes l'ancien quand tu repasses dessus. C'est honnête, et c'est exactement pour ça que `bootstrap` génère la baseline tout seul dès qu'il tombe sur un projet non-vierge. Le retrofit, c'est la vraie vie.

Le point n'est pas "jamais de baseline". C'est : ne fabrique pas ton propre legacy le jour 1. Un projet neuf où tu démarres lâche "pour aller vite", c'est une base legacy que tu t'infliges toi-même, baseline comprise, sans même l'excuse d'en avoir hérité.

## La seule ligne que tu veux vide

Le boulot de `bootstrap`, ce n'est pas d'ajouter de la qualité. C'est de rendre la ligne 1 non négociable, pour que "plus tard" n'ait jamais l'occasion d'exister. Une baseline vide reste le seul fichier d'un projet dont tu es content qu'il ne contienne rien. Le jour où tu dois le remplir, c'est que le vrai travail, tu ne l'as pas fait à la ligne 1.
