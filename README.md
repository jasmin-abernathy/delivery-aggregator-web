# delivery-aggregator-web

MVP web d’agrégation et de comparaison de services de livraison de repas.

## Objectif

Construire d’abord une interface web unifiée permettant de :

- rechercher et comparer des établissements présents sur plusieurs plateformes ;
- enregistrer les préférences de l’utilisateur ;
- indiquer si l’utilisateur dispose d’un abonnement type Uber One / Deliveroo Plus afin d’adapter l’affichage et les estimations ;
- comparer de façon transparente prix, frais, délais et avantages lorsque les données sont disponibles légalement ;
- rediriger vers le checkout officiel de la plateforme choisie afin que les avantages réels du compte utilisateur s’y appliquent ;
- conserver un cœur métier réutilisable plus tard par une PWA puis une application mobile.

## Principes non négociables du MVP

- Aucun scraping des interfaces grand public Uber Eats ou Deliveroo.
- Aucun mot de passe Uber/Deliveroo collecté par le projet.
- Aucun contournement du checkout officiel.
- Les abonnements sont d’abord des préférences déclarées par l’utilisateur, pas des droits vérifiés par une API tierce inexistante.
- Toute estimation doit être clairement distinguée d’un prix/frais confirmé par la plateforme officielle.
- Les noms des plateformes peuvent servir à identifier les services comparés, sans laisser entendre une affiliation ou un partenariat inexistant.

## Structure initiale

```text
public/
  index.html          Prototype web déployable immédiatement

docs/
  ARCHITECTURE.md     Architecture cible et modèle de données
  LEGAL-GUARDRAILS.md Contraintes à préserver pendant le développement
```

## Trajectoire

1. Prototype web statique et UX.
2. Modèle normalisé d’établissements/offres et données de démonstration.
3. Deep links vers les plateformes officielles.
4. Sources de données autorisées : saisie partenaire, flux commerçants, API officielles accessibles.
5. Backend léger et comptes utilisateurs si nécessaire.
6. PWA installable.
7. Application mobile réutilisant le même modèle métier et les mêmes APIs.

## Déploiement du prototype

Le contenu de `public/` peut être servi tel quel par un hébergement web classique. Aucun secret ni token ne doit être placé dans ce dossier.

## Statut

Phase 0 — dépôt et fondations du MVP web.
