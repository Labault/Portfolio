---
title: Durcir un VPS, c'est surtout se protéger de soi-même
date: 2026-07-07
description: Le vrai danger quand tu sécurises un serveur, ce n'est pas le botnet qui scanne ton port 22. C'est toi, à 23h, avec un port oublié dans ta règle de pare-feu.
---

Il est 23h. Tu es en SSH sur un VPS tout neuf, et tu colles la règle de pare-feu qui ferme tout, sauf les ports de ta liste. Tu valides.

Une demi-seconde trop tard, la question froide arrive : le 22, je l'ai mis dans la liste ?

Le terminal ne répond plus. Tu viens peut-être de te bannir de ta propre machine, et la seule personne habilitée à rouvrir la porte, c'était toi.

Tout le monde a vécu cette seconde-là. Ou la vivra. C'est un rite de passage de l'auto-hébergement, au même titre que le premier `rm -rf` un cran trop haut dans l'arborescence.

Quand on parle de durcir un serveur, on imagine l'ennemi dehors : le bot qui scanne ton port 22 douze fois par minute, le scan de vulnérabilités automatisé, la Chine dans tes logs d'auth. Vrai, mais ce n'est pas là que ça fait mal. Le type qui casse vraiment ta prod, c'est toi. Trois cafés dans le sang, une règle de pare-feu à laquelle il manque un port, et te voilà dehors, sans clé, avec un serveur parfaitement sécurisé contre son propriétaire légitime.

C'est le scénario que server-setup, mon converger de VPS, traite en priorité. Pas les attaquants. Moi.

L'idée tient en une phrase : aucune modification qui peut te verrouiller ne s'applique sans filet. La règle risquée part bien, mais elle part armée d'un minuteur. Le serveur attend que tu confirmes, depuis ta session, que tu es toujours là. Si la confirmation arrive, la modif est gravée. Si elle n'arrive pas dans le délai, parce que tu t'es coupé la connexion tout seul, le converger considère que quelque chose a mal tourné et annule tout ce qu'il vient de faire.

Tu te bannis. Tu attends. La porte se rouvre. Tu jures un peu, tu corriges ta liste de ports, tu recommences. Personne n'a eu besoin de la console de secours de l'hébergeur, personne n'a réinstallé quoi que ce soit.

C'est un pattern connu sous le nom de dead-man's switch : si l'humain lâche le bouton, le système revient de lui-même à un état sûr. On l'utilise pour les trains et les presses industrielles. Il se trouve qu'il marche aussi bien pour t'empêcher de te sécuriser jusqu'à l'asphyxie.

Le pendant, c'est une commande `doctor`. Avant de toucher au moindre réglage, elle inspecte l'état de la machine et te dit ce qui est cohérent et ce qui ne l'est pas. Durcir, c'est bien. Durcir en aveugle sur une machine dont tu ne connais plus l'état exact, c'est comme ça qu'on obtient une brique, très bien protégée, mais une brique.

Parce que c'est ça, le vrai risque. Un pare-feu que tu ne peux pas annuler n'est pas une mesure de sécurité, c'est un pistolet chargé braqué sur ton propre uptime. La question n'est jamais « est-ce que c'est verrouillé », c'est « est-ce que je peux revenir si je me suis trompé ».

On peut trouver ça parano pour un VPS à cinq euros par mois. L'hébergeur a une console de secours, non ? Si. Sauf que la console de secours, on la découvre toujours au pire moment, et jamais avec le bon layout clavier. Un dead-man's switch, c'est juste refuser de dépendre de son pire quart d'heure.

L'infra que personne ne documente, celle qui n'a pas de tuto sexy et qu'on découvre le soir où on est coincé dehors, c'est exactement celle qui décide si « durci » veut dire sécurisé, ou juste inaccessible. Y compris à toi.
