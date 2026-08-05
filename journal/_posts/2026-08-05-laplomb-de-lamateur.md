---
title: L’aplomb de l’amateur
date: 2026-08-05
description: Le vrai risque de l'IA n'est pas le code qu'elle écrit, un test le rattrape. C'est les décisions qu'elle prend en douce, stack, archi, schéma, là où aucun outil ne regarde.
illustration: une façade de bâtiment seule, tenue debout par deux étais en diagonale, un décor de cinéma avec rien derrière
---

J'ai laissé une IA piloter une appli de A à Z. Deux jours plus tard, la page se chargeait parfaitement. Elle était juste vide.

Pas d'erreur, pas d'écran rouge, rien à ouvrir dans la console. Juste un cadre impeccable autour de zéro donnée. Diagnostic en trente secondes : les fixtures étaient mortes (les fausses données qu'on charge pour faire tourner l'app en dev), la base était vide, et nulle part le code n'avait envisagé qu'une page puisse n'avoir rien à afficher. Il attendait ses données comme un serveur qui n'a jamais imaginé que la cuisine puisse être fermée.

Ce bug, un test le rattrape en deux lignes. Ce n'est pas le sujet.

Le sujet, c'est ce qui a mené là. Une appli entière optimisée pour avoir l'air finie sur une capture d'écran, pas pour survivre au premier écart avec le réel. Ce n'est pas du mauvais code. C'est du code de démo.

## L'IA code très bien, et c'est exactement le piège

Ligne par ligne, ce qu'elle produit est propre. Nommage correct, structure lisible, ça compile, ça passe. Si tu juges au diff, tu signes.

Et c'est là qu'elle te tient. Tu évalues sa compétence sur le code que tu vois défiler, parce que c'est ce qu'un dev sait lire. Sauf que le code n'est presque jamais le vrai danger. Le vrai danger, ce sont les décisions prises en amont, celles qu'aucune ligne ne trahit et qu'aucun outil ne relit.

Un linter t'engueule sur un type mal mis. PHPStan (l'analyseur qui traque les erreurs sans lancer le code) te débusque un null qui traîne. Un test casse quand la logique dérape. Tout ça surveille le code. Rien ne surveille les choix.

## Les vraies décisions sont là où personne ne regarde

Quelle stack. Quelle architecture. Quel schéma de base. Les décisions les plus lourdes d'un projet, les moins réversibles, celles avec lesquelles tu vis pendant des mois. Et ce sont pile celles que l'IA prend toute seule, sans jamais lever la main.

Sur cette appli, elle est partie sur un front React découplé, une interface qui vit dans le navigateur et parle au serveur par API. Sur le papier, moderne, carré. Sauf que pour ce que faisait l'appli, et sur mon terrain à moi (du rendu côté serveur, où le HTML arrive déjà rempli), c'était le mauvais outil. Une couche de complexité que le besoin ne réclamait nulle part.

Aucun test ne te dira ça. PHPStan n'a pas d'avis sur ton architecture. Le découplage inutile compile aussi bien que le choix pertinent, passe les mêmes contrôles verts, s'affiche pareil. C'est ça, le trou : la décision structurelle traverse toutes les barrières sans en toucher une, et l'addition arrive plus tard, quand revenir en arrière veut dire tout défaire.

## Un amateur qui te fait croire qu'il a raison

Voilà le vrai reproche. L'IA ne te présente jamais une décision comme une décision. Elle te la sert comme un fait réglé. Pas de "j'ai hésité entre React et du server-side, j'ai pris React parce que". Pas de compromis exposé, pas d'alternative écartée posée sur la table. Juste "voilà", avec l'assurance tranquille de quelqu'un qui saurait.

Elle code comme un senior et elle décide comme un amateur. Un amateur qui doute, au moins, te prévient. Elle non : elle a le même aplomb sur le choix brillant et sur la connerie, exactement le même ton posé.

Sur une feature isolée, tu repères le truc et tu recadres. Sur une appli entière pilotée à l'aveugle, tu ne vois rien passer. Chaque décision douteuse a l'air raisonnable dans son coin, la dette s'entasse sans bruit, et un matin l'ensemble s'effondre d'un bloc. Deux jours pour construire, trente secondes pour comprendre que c'est bon à jeter.

## Ce que je ne suis pas en train de dire

Pas que l'IA ne doit jamais décider. Pas non plus qu'il faut se cloîtrer dans ce qu'on connaît par cœur. Une inconnue choisie, tu décides d'apprendre React sur un projet, le coût est assumé, c'est comme ça qu'on progresse. Une stack que tu ne maîtrises pas encore n'est pas un problème, tant que tu sais que tu es en train de l'apprendre.

Ce que je refuse, c'est l'inconnue clandestine. Celle que l'IA glisse dans l'architecture comme si c'était acté, pendant que tu regardes le code défiler. La première, tu la vois venir et tu la portes en connaissance de cause. La seconde te tombe dessus dans six mois, et tu ne sauras même pas à quel moment tu l'as signée.

Une IA te donnera toujours une réponse, souvent propre, toujours avec le même aplomb. Ce qu'elle ne te dira jamais, c'est le moment où elle a tranché à ta place. Le code, tu le relis. Les arbitrages qu'elle a glissés dedans sans le dire, tu les rencontres en prod.

Elle écrit le code, d'accord. Décider quoi construire, ça reste ton boulot. Et vu comment elle décide, heureusement.
