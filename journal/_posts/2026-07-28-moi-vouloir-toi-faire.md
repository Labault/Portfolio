---
title: Moi vouloir, toi faire
date: 2026-07-28
description: Un prompt d'agent n'est pas une commande qu'on exécute, c'est un extrait de texte que le modèle complète. Cadrage et politesse ne sont pas du bruit, ils orientent la complétion vers les bonnes réponses.
illustration: une paroi de grotte avec des pictogrammes peints à la main (pas de clavier, pas de tech, du pariétal pur)
---

Mes prompts d'agent ressemblent à du langage préhistorique. `MOI VOULOIR`, `TOI FAIRE`, des impératifs chiffrés, pas un "merci". Neuf prompts de feature dans Mariol-API, zéro politesse. J'étais convaincu que c'était la forme la plus rigoureuse : que du signal, aucun gras.

Puis j'ai fini par remarquer un truc gênant. Les prompts où je prenais le temps d'écrire proprement, de demander de réfléchir, de dire merci, sortaient du meilleur code.

Ça n'a aucun sens si l'agent obéit à des ordres. Ça en a beaucoup s'il fait autre chose.

Ton agent ne lit pas tes ordres. Il complète ton texte.

## Il ne t'obéit pas, il te complète

Un LLM ne lance pas ton instruction comme une fonction. Il produit la suite la plus probable de ton texte, d'après tout ce qu'il a avalé à l'entraînement. Ton prompt n'est donc pas une commande. C'est un sélecteur : il décide quelle région d'internet le modèle va imiter pour te répondre.

Et là, tout se joue. Écris ton prompt comme une engueulade sur Stack Overflow, tu récoltes une réponse d'engueulade sur Stack Overflow. Écris-le comme le haut d'une doc bien tenue, tu récoltes de la doc. Le modèle ne juge pas la qualité de ta demande. Il repère à quoi elle ressemble, et il va chercher les réponses qui traînent dans le même quartier.

## Deux leviers qu'on confond

Le réflexe télégraphique mélange deux choses : être précis, et être sec.

La précision, c'est le contenu de la contrainte. "Pagination par curseur, 50 par page, PHPStan niveau 9" bat "tu pourrais ajouter de la pagination si possible" à tous les coups, parce que les mots-clés techniques ancrent le modèle dans la doc, tandis que le flou poli l'ancre dans le blog SEO qui paraphrase la doc. Ça, mon `TOI FAIRE` le fait très bien.

Le cadrage, c'est autre chose. C'est le registre dans lequel tu écris, et il pèse autant que tes contraintes. Le réflexe caverne a raison sur la précision. Il a tort de croire que le cadrage n'était que du bruit à couper.

## Dis-lui de réfléchir, et de le faire en expert

Ajoute "prends le temps, raisonne étape par étape" et la qualité monte, sur tout ce qui demande un raisonnement. Ce n'est pas magique : tu forces le modèle à poser ses étapes avant de conclure, et un raisonnement correct rend une conclusion correcte plus probable. Les bonnes réponses, dans ses données, montrent leur travail. Les mauvaises balancent le résultat et s'en vont.

"Raisonne comme un sénior" joue sur le même clavier, en plus discret. Ça ne lui apprend pas Postgres. Ça l'oriente vers le registre des gens qui en parlent bien. Honnêtement, c'est le levier le plus surestimé du lot : il change le ton et l'assurance plus souvent que l'exactitude. Utile, pas miraculeux.

## Le "merci" n'est pas pour la machine

Le plus contre-intuitif, c'est la politesse. Elle n'a aucune raison d'aider si le modèle exécute. Elle en a une s'il complète.

Sur un forum, la réponse acceptée, celle qui a été upvotée, vit sous trois "merci, tu m'as sauvé ma soirée". La réponse pourrie, elle, est sous un "RTFM" et un downvote. Le modèle a tout lu. Un prompt courtois ressemble aux échanges qui finissent bien, et il pointe vers le quartier où traînent les réponses qui marchent.

Je n'ai pas de preuve chiffrée à te vendre, et l'effet est mitigé selon les modèles. Mais le mécanisme tient, et depuis que je dis merci, je n'ai rien à y perdre.

## Le piège, c'est d'y croire trop fort

Là où j'ai dérapé, c'est en empilant. "Tu es le meilleur expert du monde, c'est vital, réfléchis très très fort." À ce jeu, tu n'obtiens pas un meilleur prompt, tu obtiens un prompt anxieux. Un agent à qui tu répètes que tout est capital finit par tout valider, tes mauvaises idées comprises. Le cadrage aide jusqu'au point où il vire à l'incantation, et ce point arrive plus vite qu'on croit.

Je continue d'écrire `MOI VOULOIR`, `TOI FAIRE` pour mes contraintes. Le télégraphe avait raison là-dessus. Mais maintenant, je dis merci. Pas pour les sentiments de la machine : elle n'en a pas. Parce qu'elle a lu internet, et que sur internet, "merci" est ce qu'on écrit sous la réponse qui a marché.
