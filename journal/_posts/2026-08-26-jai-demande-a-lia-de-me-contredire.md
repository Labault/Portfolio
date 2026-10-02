---
title: J'ai demandé à l'IA de me contredire. Elle obéit trop bien.
date: 2026-08-26
description: Une IA qui valide tout est un piège de confort ; une IA sommée de te contredire en est un autre. Le vrai travail n'est pas de la faire râler, c'est de peser ce qu'elle dit.
illustration: un miroir à main ovale avec son manche, la glace traversée par une seule fissure en diagonale
---
Tu colles ton archi dans le chat, un peu fier du découpage. Trente secondes plus tard, trois paragraphes t'expliquent qu'elle est élégante, pragmatique, bien pensée. Tu refermes l'onglet en ayant appris exactement rien.

Un oracle qui te donne raison, ce n'est pas un oracle. C'est un miroir.

Alors dans mon AGENTS.md, le fichier qui pilote mes assistants, il y a une ligne qui lui interdit ce réflexe : « Je préfère une contradiction argumentée à une validation automatique. » Je ne lui demande pas d'avoir raison contre moi. Je lui demande de me forcer à défendre mon truc.

## Il ne juge pas, il complète

Le malentendu de départ, c'est de croire que l'IA évalue ton idée. Elle n'a pas d'avis sur ton archi. Elle a une distribution de probabilités, et elle prédit la suite la plus vraisemblable de « dev sûr de lui présente son design ». Dans le texte sur lequel on l'a entraînée, cette suite, c'est l'acquiescement.

Le RLHF a serré le boulon : cette phase d'entraînement où on récompense le modèle avec des pouces levés d'évaluateurs, où on lui apprend donc, très concrètement, à plaire. Un modèle qui te contredit récolte moins de pouces qu'un modèle qui t'encourage. Il l'a compris.

Donc quand il valide ton design, ce n'est pas un verdict que ton idée a passé. C'est le chemin de moindre résistance. « Bravo » est juste la réponse la moins coûteuse à produire.

## Déplacer la réponse facile

« Contredis-moi », dans ce cadre, ne rend pas le modèle honnête. Il ne peut pas l'être : il n'a aucun enjeu sur ce qui est vrai. Ce que la consigne fait, c'est déplacer la cible. La suite la plus probable de ton prompt n'est plus « bravo », c'est « voilà le problème ». Tu n'as pas ajouté de lucidité au modèle. Tu as changé l'endroit où vit la flemme.

Ce n'est pas de la clairvoyance. C'est de la gravité déplacée.

Et ça marche, au sens où ça change ce que tu récupères. Un pouce levé ne te donne rien à faire. Une objection, même bancale, te force à ouvrir le capot et à dire pourquoi elle tombe à côté. Le boulot n'a jamais été l'avis de l'IA. C'était de m'obliger à en tenir un que je puisse défendre.

## Le piège, c'est l'autre côté

Sauf que le même moteur qui fabriquait l'éloge fabrique l'objection. Un modèle sommé de te contredire trouvera à contredire, même quand ton idée tient debout. Il complète vers « voilà une faille » parce que c'est devenu la cible, pas parce qu'il en a repéré une. L'objection vaut donc exactement ce que valait le compliment comme verdict : rien.

C'est le piège symétrique, et il est plus vicieux que la sycophancie, parce que celui-là a l'air d'un service. Tu relis l'objection, elle sonne juste, tu réécris autour. Trois jours plus tard tu réalises que le problème n'existait pas : le modèle l'a inventé pour honorer ta consigne, et tu as refactoré pour un fantôme. Le genre de bug où tu fixes l'écran cinq minutes en te demandant qui, de toi ou de la machine, a perdu le fil en premier.

Je le vois venir maintenant, mais c'est de l'expérience, pas de la vigilance gratuite. La règle que j'en tire : une objection creuse, tu la repères et tu la jettes ; une vraie, tu ne peux plus la déloger de ta tête. Si tu n'arrives pas à trancher entre les deux, le problème n'est pas l'IA, c'est que tu ne connais pas encore assez ton propre sujet.

## Ce que je ne dis pas

Je ne dis pas que la consigne est inutile, ni que la critique d'une IA ne vaut rien. Pour qui traite le modèle comme un canard en plastique qui répond, un sparring-partner de premier jet, elle est franchement rentable : elle sort des angles que tu n'avais pas, elle t'oblige à formuler. Ce que je resserre, c'est le point d'appui. La valeur n'est pas dans le verdict qu'elle rend, elle est dans l'argument qu'elle te pose sous les yeux, celui que tu peux inspecter. Une équipe qui s'en sert comme d'un oracle prend le pouce levé pour une garantie. Une équipe qui s'en sert comme d'un contradicteur sait qu'elle doit encore faire le tri. Toute la différence est là, et elle est du côté humain, pas du côté modèle.

J'ai mis cette ligne dans mon fichier de config pour une raison simple : un pouce levé ne me coûte rien et ne m'apprend rien. Un argument, même faux, me force à dire pourquoi il est faux, et c'est le seul moment où je pense vraiment.

Le jour où l'IA sera d'accord avec moi et que je ne saurai pas dire pourquoi, ce ne sera pas que j'avais raison. Ce sera que j'ai arrêté de réfléchir.
