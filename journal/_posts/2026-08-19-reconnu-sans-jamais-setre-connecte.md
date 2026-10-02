---
title: Reconnu sans jamais s'être connecté
date: 2026-08-19
description: Sur mobile, recopier le flow login/mot de passe du web est souvent le mauvais réflexe. Le vrai travail n'est pas d'authentifier un compte, c'est de reconnaître un appareil. Et ça rebat jusqu'à la mécanique d'ami.
illustration: une grande empreinte digitale seule, qui occupe tout le centre de la pièce, six ou sept arcs concentriques irréguliers avec un tourbillon au cœur
---
Tu construis l'app mobile, et le réflexe tombe tout seul : tu dessines l'écran de login. Email, mot de passe, "mot de passe oublié ?", le mail de confirmation, tout le cortège. Sur ce projet, cet écran n'existe pas. Tu ouvres l'app, elle sait déjà qui tu es. Tu ne lui as jamais donné ton nom.

Recopier l'écran de login du web sur mobile, c'est le réflexe. C'est souvent le mauvais.

Ce n'est pas un compte que tu authentifies. C'est un appareil que tu reconnais.

## La connexion sans connexion

Sur le web, l'identité est quelque chose que l'utilisateur sait : un mot de passe, rejoué à chaque session. L'idée, c'est de déplacer le secret. L'appareil en génère un, une fois, et le garde. Le serveur ne le voit jamais : il n'en conserve qu'une empreinte, un hash, qu'on ne peut pas remonter en sens inverse. À partir de là, prouver que tu es toi revient à prouver que l'appareil détient le secret.

Ce que tu viens de retirer du plateau, c'est l'écran le plus attaqué du web. Pas de mot de passe à hameçonner, pas de parcours de "mot de passe oublié" à retourner contre toi, pas de colonne d'emails qui attend tranquillement sa fuite. L'identifiant réel de l'appareil, lui, ne quitte jamais l'appareil.

Tu n'as pas sécurisé le login. Tu l'as supprimé.

## Là où l'"ami" se réinvente

Vient la conséquence dont personne ne te prévient. Sans compte, il n'y a plus rien pour désigner quelqu'un. Pas de pseudo, pas d'adresse mail, pas d'"ajouter par identifiant". "Ami" ne peut plus être une recherche.

Alors ça devient un geste physique. Vous êtes dans la même pièce, un appareil affiche un QR, l'autre le scanne. Le serveur relie les deux derrière des codes jetables, jamais les identifiants bruts. Le graphe social se construit par proximité et possession, pas par annuaire.

C'est là ce que le modèle t'apprend vraiment. Faire vivre l'identité dans l'appareil ne rebat pas que l'auth. Ça force chaque primitive sociale, l'ami, l'invitation, le partage, à se réécrire en capacité qu'on échange entre deux appareils, au lieu d'une ligne qu'on insère entre deux identifiants.

## L'identifiant qu'on ne montre à personne

Reste une porte à ne jamais laisser ouverte. Le jour où tu laisses filer l'identifiant réel de l'appareil, glissé dans le QR pour aller plus vite, renvoyé dans une réponse d'API par distraction, tu viens de graver un passe-partout. Définitif.

Parce que cet identifiant n'est pas une donnée parmi d'autres. Il est l'identité. Une clé d'API, tu la révoques et tu en régénères une. Celui-là, tu ne peux pas : le révoquer, ce serait effacer la personne.

D'où le code jetable à la place, court, mort après usage. Pas un raffinement, la condition d'entrée. On expose l'UUID une seule fois et il est compromis pour de bon, parce qu'il n'existe aucun "changez votre mot de passe" quand le mot de passe, c'est l'appareil.

## Quand le compte gagne quand même

Je ne dis pas que le login/mot de passe est une erreur. Le jour où l'identité doit te suivre d'un appareil à l'autre, où il y a de l'argent en jeu, où un humain au support doit pouvoir te rendre l'accès, le compte gagne son salaire. Les passkeys, d'ailleurs, font le tour de force sans rien jeter : un secret lié à l'appareil, mais adossé à une vraie reprise.

Parce que le prix, il est là, entier. Perdre l'appareil, c'est se perdre. Pas de compte, donc pas de "mot de passe oublié", par construction. Tu vides le stockage du navigateur, tu changes de téléphone, et l'identité s'en va avec. Aucun back-office ne te repêchera, puisque tout l'intérêt était que le serveur n'ait jamais eu les clés de ta porte.

L'identité-dans-l'appareil brille à un seul endroit : l'enjeu faible, l'appareil unique, la vie privée avant tout. Là où un formulaire d'inscription tuerait le projet avant qu'il démarre, et où perdre son état est un agacement, pas un désastre. Sur un truc comme cd projet, c'est précisément le marché qu'on signe en connaissance de cause.

Un écran de login que tu ne construis pas, c'est un écran que tu n'auras jamais à défendre. Ce projet échange la reprise contre le fait de n'avoir jamais eu à demander ton nom. Pour ce qu'il est, ce n'est pas un compromis. C'est la fonctionnalité.
