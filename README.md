# delivery-aggregator-web

MVP web d’agrégation et de comparaison de services de livraison, de retrait, de repas, de courses et d’offres anti-gaspi.

## Objectif

Construire d’abord une interface web unifiée permettant de :

- rechercher et comparer des établissements ou services présents sur plusieurs plateformes ;
- filtrer séparément par plateforme, par mode « Livraison » / « Retrait » et par type d’offre « Repas » / « Courses » / « Anti-gaspi » ;
- enregistrer localement les préférences de l’utilisateur ;
- indiquer si l’utilisateur dispose d’un abonnement type Uber One / Deliveroo Plus sans prétendre vérifier ce droit ni recalculer un tarif ;
- comparer de façon transparente prix, frais, délais et avantages lorsque les données sont disponibles légalement ;
- rediriger vers le checkout officiel de la plateforme choisie uniquement lorsqu’une URL officielle HTTPS a été vérifiée ;
- conserver un cœur métier réutilisable plus tard par une PWA puis une application mobile.

## Principes non négociables du MVP

- Aucun scraping des interfaces grand public des services comparés.
- Aucun identifiant, mot de passe, cookie de session ou jeton de compte tiers collecté par le projet.
- Aucun contournement du checkout officiel.
- Les abonnements sont des préférences déclarées par l’utilisateur, pas des droits vérifiés.
- Toute estimation doit être clairement distinguée d’un prix/frais confirmé par la plateforme officielle.
- Les noms des plateformes servent uniquement à identifier les services comparés, sans laisser entendre une affiliation ou un partenariat inexistant.
- Une valeur absente reste absente : le prototype n’invente pas un total ou un classement pour combler des données manquantes.

## Structure du prototype

```text
public/
  index.html                Structure accessible de l’interface
  styles.css                Mise en page responsive et styles
  app.js                    Recherche, filtres, favoris et stockage local
  data/
    demo-restaurants.js     Fixtures explicitement fictives

docs/
  ARCHITECTURE.md           Architecture cible et modèle de données
  LEGAL-GUARDRAILS.md       Contraintes à préserver pendant le développement
```

## Tester localement

Aucune installation ni étape de build n’est nécessaire. Le dossier `public/` doit simplement être servi par un serveur HTTP statique.

Exemples, selon les outils déjà disponibles sur la machine :

```bash
python3 -m http.server 8080 --directory public
```

ou :

```bash
php -S 127.0.0.1:8080 -t public
```

Puis ouvrir `http://127.0.0.1:8080/`.

Le prototype fonctionne sans API externe et sans secret. Les trois fonctionnalités persistées — favoris, déclaration Uber One et déclaration Deliveroo Plus — utilisent `localStorage` avec repli gracieux si le stockage est indisponible.

## Données de démonstration

Le fichier `public/data/demo-restaurants.js` contient uniquement des établissements/services et montants inventés. Une offre peut appartenir à une ou plusieurs catégories (`meal`, `grocery`, `anti-waste`) indépendamment de son mode (`delivery`, `pickup`). Une offre porte un état de donnée (`demo`, `confirmed`, `estimated`, `unknown`), une source, une date de vérification facultative et des champs potentiellement nuls.

Le statut `confirmed` ne doit jamais être appliqué à une fixture. Les boutons de commande restent désactivés tant qu’une URL officielle HTTPS n’a pas été fournie et validée contre une liste d’hôtes explicitement autorisés.

## Trajectoire

1. Prototype web statique et UX interactif — **lot de démonstration réalisé**.
2. Valider le périmètre géographique et les colonnes réellement utiles.
3. Choisir une source de données autorisée et une stratégie de liens officiels.
4. Brancher uniquement les données/URLs dont l’usage est validé.
5. Ajouter un backend léger et des comptes seulement si le besoin est démontré.
6. PWA installable.
7. Application mobile réutilisant le même modèle métier et les mêmes APIs.

## Déploiement du prototype

Le contenu de `public/` peut être servi tel quel par un hébergement web classique. Aucun secret ni token ne doit être placé dans ce dossier.

Aucun déploiement public n’est déduit automatiquement de ce dépôt : la cible et le mécanisme de publication doivent être validés avant toute mise en ligne.

## Statut

Phase 1 — prototype statique interactif, autonome et testable avec données fictives.
