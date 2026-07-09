---
title: Je ne relis pas le code de l'IA, je l'empêche de faire des bêtises
date: 2026-07-14
description: Superviser une IA, ce n'est pas relire chaque ligne, c'est construire des garde-fous qu'elle ne peut pas franchir. Le prompt parfait se banalise en six mois. Le jugement et la vérification automatisée, non.
illustration: un rempart de pierre (garde-fous)
---

L'IA écrit le code, tu le relis ligne par ligne, tu valides. Le réflexe tient sur trois lignes. Sur une session où l'agent a touché quinze fichiers, il s'effondre : tu ne lis plus, tu fais défiler, et tu tapes « LGTM » sur une diff parcourue en diagonale en te convainquant que c'était de la supervision.

Ça ne passe pas à l'échelle, alors je ne le fais pas. Ce n'est pas relire chaque ligne. C'est construire les murs qu'elle ne peut pas franchir.

## La consigne est molle, le garde-fou est dur

Mon agent lit un `AGENTS.md` global : mes priorités, mes anti-patterns, « tu ne committes jamais toi-même ». C'est le noyau qui le fait raisonner comme moi. Mais un fichier d'instructions, même bien écrit, reste un indice, pas une loi : le modèle peut l'ignorer, mal l'interpréter, ou juste avoir un mauvais jour statistique.

Le vrai verrou est ailleurs. Un hook `PreToolUse` de Claude Code s'exécute avant chaque commande de l'agent et peut la refuser. Le mien intercepte `git commit` et `git push` : l'agent finit sa tâche, tente un commit, se fait jeter avec un message qui lui explique pourquoi, s'adapte, stage, et me tend le message à valider. Le détail qui change tout : ce refus tient même quand je lance l'agent avec le flag dont le nom devrait déjà m'alerter, `--dangerously-skip-permissions`. La consigne polie est devenue une loi qu'on ne débraye pas.

## Des barrières, pas un relecteur

Autour, une pile de verrous déterministes qui ne dépendent ni de mon attention ni de la bonne volonté du modèle. `commitlint` refuse un message hors format. `gitleaks`, greffé sur `pre-commit`, bloque tout commit contenant un secret, quel que soit qui le lance : moi, Claude Code ou Codex. PHPStan niveau 9 refuse l'erreur de type avant la revue, avec une baseline qui fige la dette existante et interdit d'en ajouter.

Aucun ne lit le code « intelligemment ». Chacun vérifie une propriété, toujours la même, à chaque passage, sans se déconcentrer à 18h. C'est bien pour ça qu'ils valent mieux que mes yeux en fin de journée.

## La compétence rare n'est pas celle qu'on croit

Le « prompt engineering » a le même avenir que le métier de webmaster en 2005 : dans six mois, tout le monde saura tourner une consigne correcte, et les modèles comprendront de mieux en mieux les consignes bâclées. Ce qui ne se banalise pas, c'est de savoir quelle barrière poser et quelle propriété vérifier pour qu'une sortie d'IA soit sûre par construction.

Ça ne veut pas dire ne jamais la relire. Sur une décision d'archi ou une frontière de sécurité, je lis et je conteste. Mais je lis là où il faut du jugement, pas là où il faut de la vigilance. Écrire le prompt, la prochaine version du modèle le fera à ta place. Décider quels murs dresser autour d'elle, non.
