---
title: Greffer une API REST sur dix ans de legacy
date: 2026-06-23
description: Ajouter une API à dix ans de Symfony rendu côté serveur. Le vrai chantier n'était pas d'écrire des endpoints, c'était de rendre partageable une logique métier qui ne l'avait jamais été, sans casser ce qui tournait.
thumbnail: assets/journal/greffer-une-api-rest-sur-dix-ans-de-legacy.svg
---

Le jour où on a décidé d'ajouter une API à QuizzWeb, elle n'existait pas. Pas "elle était mal foutue". Elle n'existait pas. Dix ans de Symfony rendu côté serveur, des contrôleurs qui crachaient du HTML, et toute la logique métier coincée dedans, pensée pour une seule façon d'être appelée : par un humain devant un navigateur.

Ajouter une API à ça, ce n'est pas créer un dossier `/api`. C'est exposer une logique qui n'a jamais été conçue pour être exposée.

## Le piège : le copier-coller qui crée un deuxième produit

Le chemin facile est évident. Tu crées des contrôleurs d'API, tu réécris dedans ce dont l'API a besoin, et en deux semaines tu as des endpoints qui marchent. Le problème arrive trois mois plus tard : tu as maintenant deux implémentations de la même règle métier, une pour le web, une pour l'API, et elles divergent. Tu n'as pas construit une API. Tu as forké ton produit.

La règle qu'on s'est fixée : l'API et l'appli web appellent la même logique. Une seule source de vérité par règle. Ce qui veut dire que le vrai chantier n'était pas d'écrire des endpoints, c'était de sortir la logique métier des contrôleurs HTML pour que les deux mondes puissent la partager. Du refactoring de legacy, déguisé en projet d'API.

Et tout ça sans casser l'appli existante, qui continuait de livrer chaque semaine. On extrayait, on vérifiait que le web tournait toujours pareil, on exposait. Jamais l'inverse.

## L'auth : sécurisé d'abord, parfait ensuite

L'API avait besoin d'authentification avant que le système final soit prêt. Plutôt que de bloquer la mise en service en attendant la solution idéale, on a démarré avec des tokens générés à la main : une API sécurisée et utilisable tout de suite, derrière un vrai consommateur, quitte à durcir le mécanisme juste après. Le JWT est venu dans un second temps, une fois l'API en place et le besoin clair.

C'est un arbitrage que j'assume : un MVP sécurisé qui tourne bat un système d'auth parfait qui n'existe pas encore. À condition de tenir la deuxième étape, pas de l'oublier une fois que "ça marche".

## Une API qui pousse par les besoins, pas par un grand plan

On n'a pas dessiné l'API entière sur un tableau blanc pour la livrer d'un bloc. Chaque endpoint est né d'un besoin réel, versionné dès le départ sous `v1`, ressource par ressource. Au fil de l'eau, ça a fini par couvrir tout le domaine : sessions, examens, formations, organisations, candidatures. Une quarantaine de ressources, apparues quand elles servaient, pas avant.

Moins satisfaisant pour l'esprit d'architecte qui veut tout cadrer d'avance. Beaucoup plus solide, parce que chaque endpoint avait une raison d'exister le jour où il est arrivé.

## La preuve que c'était bien construit

Le test final, ce n'est pas un score de couverture. C'est qu'un autre produit a pu s'appuyer dessus. SignEval, une application d'émargement avec un front React confié à un alternant, consomme aujourd'hui cette même API et partage les mêmes données. L'API a cessé d'être une fonctionnalité de QuizzWeb pour devenir une plateforme.

Une logique métier dupliquée n'aurait jamais tenu cette charge. C'est là qu'on sait, après coup, qu'on n'a pas forké le produit.

## La leçon

Une API sur du legacy, c'est un projet de refactoring qui porte un costume d'API. Le travail visible, ce sont les endpoints. Le vrai travail, c'est de rendre partageable une logique qui ne l'était pas, sans casser ce qui tournait déjà. Si tu te retrouves à dupliquer du métier pour l'exposer, arrête-toi : tu n'écris pas une API, tu construis un deuxième produit que tu devras maintenir deux fois.
