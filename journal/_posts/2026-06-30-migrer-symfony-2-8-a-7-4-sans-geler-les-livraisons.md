---
title: Migrer Symfony 2.8 à 7.4 sans geler les livraisons
date: 2026-06-30
description: 112 000 lignes, une release par semaine, zéro gel de feature. Comment on fait passer une codebase de PHP 7.0 / Symfony 2.8 à PHP 8.4 / Symfony 7.4 en livrant, pas en s'arrêtant.
thumbnail: assets/journal/migrer-symfony-2-8-a-7-4-sans-geler-les-livraisons.svg
---

Tout le monde connaît le piège. Une codebase qui a dix ans, un framework cinq versions en retard, et quelqu'un qui propose le grand rewrite. Six mois plus tard le rewrite n'est pas fini, le produit n'a pas avancé, et la dette a fait des petits sur les deux fronts.

Sur QuizzWeb, un SaaS d'examens pour centres de formation, ce piège n'était même pas une option. 112 000 lignes de PHP, une centaine d'environnements clients, et une livraison par semaine non négociable. On ne pouvait pas s'arrêter de livrer pour migrer. Il fallait migrer **en** livrant.

Voilà comment on est passés de PHP 7.0 / Symfony 2.8 à PHP 8.4 / Symfony 7.4 sans jamais geler une feature.

## La règle : pas de branche au long cours

La première décision, c'est ce qu'on ne fait pas. Pas de branche "migration" qui vit trois mois à côté de `main` et qu'on prie pour merger un jour. Chaque incrément part en production avec le reste, dans le cycle hebdomadaire normal. Si un changement ne peut pas cohabiter avec le code existant, c'est qu'il n'est pas prêt : on le redécoupe.

Concrètement, on monte de version mineure en version mineure, on adapte ce que la nouvelle version casse, on livre, on recommence. C'est moins héroïque qu'un grand soir. C'est surtout beaucoup moins risqué.

## L'outillage qui rend ça tenable

Migrer 112k lignes à la main, c'est la recette du burnout avec des bugs en prime. Trois outils ont fait le gros du travail.

**Rector** pour les rewrites mécaniques. Les changements répétitifs entre versions de PHP et de Symfony (signatures, annotations vers attributs, dépréciations) c'est lui qui les applique, de façon reproductible. Ce qu'un humain ferait mal sur 800 fichiers, une règle Rector le fait bien, et pareil partout.

**PHPStan avec une baseline.** C'est l'astuce qui change tout. La baseline fige la dette existante et interdit d'en ajouter : le niveau monte, la baseline rétrécit, jamais l'inverse. La dette se rembourse, elle ne s'accumule plus.

**La CI comme filet.** Chaque incrément passe les tests et l'analyse statique avant la prod. Pas de "ça devrait marcher" : la CI tranche.

## Là où ça a fait mal

La théorie est propre. La réalité l'est moins. Deux pièges qui m'ont coûté bien plus qu'une après-midi :

Un emoji. Saisi par un utilisateur dans un champ texte, et tout un pan de l'appli se couche, parce que l'encodage historique n'avait jamais prévu des caractères sur quatre octets. Le genre de bug sans aucune dignité : tu déroules une stack trace sérieuse pendant une heure pour finir devant un drapeau rouge tapé par un client. Une migration, ce n'est pas que des dépréciations de framework. C'est surtout dix ans de données réelles que personne n'avait encodées proprement, et qui choisissent ce moment pour se rappeler à toi.

L'import/export XML qui dialogue avec un autre de nos produits. Sur le papier, un détail. En pratique, une laisse. Ce format était un contrat entre deux systèmes : impossible de faire évoluer le modèle de données d'un côté sans risquer de casser l'autre en bout de chaîne. Chaque refonte se cognait à la même question, est-ce que ça reste compatible avec le XML ? La migration interne, tu la pilotes. Un contrat partagé entre deux produits, c'est lui qui pilote ton calendrier. C'est là qu'on comprend que migrer, ce n'est pas un problème de code, c'est un problème de dépendances.

## Le résultat

PHP 8.4, Symfony 7.4, 251 releases plus loin. Pas un seul gel de feature sur toute la durée. Le produit a continué de sortir des évolutions pendant qu'il changeait de fondations, et les clients n'ont jamais vu la différence. C'est exactement le but.

La leçon, je la garde courte : la dette technique se rembourse en livrant, pas en arrêtant tout. Le grand rewrite est presque toujours le mauvais pari. La migration incrémentale est moins glorieuse à raconter, mais c'est elle qui arrive en prod.
