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
- L’inscription facultative aux nouvelles de Sans Effort exige une case de consentement explicite et un test anti-robot local ; il n’y a pas de double opt-in.
- Aucun CAPTCHA tiers, aucune clé Mailjet et aucun secret Potager ne sont exposés au navigateur.

## Structure du prototype

```text
public/
  index.html                Structure accessible de l’interface
  styles.css                Mise en page responsive et styles
  app.js                    Recherche, filtres, favoris et stockage local
  survey.html               Questionnaire public accessible, une question par écran
  survey.css                Chronologie, progression et mise en page du questionnaire
  survey.js                 Reprise locale/e-mail, récapitulatif et versioning côté UI
  results.html              Page dédiée aux résultats agrégés
  results.js                Accès local ou OTP et rendu des répartitions
  api/
    newsletter.php          Consentement + test anti-robot + connecteur Potager Mailing
    survey-lib.php          SQLite privé, schéma du questionnaire et helpers
    survey.php              Chargement/enregistrement/versioning
    auth.php                OTP à usage unique et sessions d’une heure
    results.php             Agrégats des seules participations exploitables
  data/
    demo-restaurants.js     Fixtures explicitement fictives

docs/
  ARCHITECTURE.md           Architecture cible et modèle de données
  LEGAL-GUARDRAILS.md       Contraintes à préserver pendant le développement
```

## Tester localement

Aucune installation ni étape de build n’est nécessaire pour le comparateur. Le dossier `public/` doit simplement être servi par un serveur HTTP.

Exemples, selon les outils déjà disponibles sur la machine :

```bash
python3 -m http.server 8080 --directory public
```

ou, pour pouvoir également exécuter le connecteur PHP :

```bash
php -S 127.0.0.1:8080 -t public
```

Puis ouvrir `http://127.0.0.1:8080/`.

Le comparateur lui-même fonctionne sans API externe et sans secret. Les préférences locales utilisent `localStorage` avec repli gracieux si le stockage est indisponible.

Le connecteur facultatif d’inscription e-mail est différent : il nécessite côté serveur le secret partagé `SANS_EFFORT_MAILING_SECRET` (ou le fichier privé correspondant), jamais dans `public/`. Le formulaire public doit récupérer un petit défi anti-robot auprès de `/api/newsletter.php`, exiger sa résolution et une case de consentement avant de transmettre l’adresse à la liste privée `sans-effort-testers` du Potager Mailing.

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

Le prototype public visé est `sanseffort.lepotager.org`. Le mécanisme de déploiement reste à brancher explicitement ; aucune mise en ligne n’est déduite automatiquement du dépôt.

## Statut

Phase 1 — prototype interactif + questionnaire public versionné prêts sur la branche de travail ; données comparateur encore fictives.


## Questionnaire public

Le questionnaire est volontairement indépendant des comptes des plateformes et ne requiert pas de compte Sans Effort.

- progression calculée uniquement sur les questions essentielles ;
- envoi autorisé même si elles ne sont pas toutes complétées ;
- aucune réponse incomplète n’entre dans les analyses ;
- reprise du même brouillon tant qu’il n’a jamais été exploitable ;
- après une première version exploitable, une modification crée une nouvelle version ; seule la plus récente compte dans les agrégats ;
- l’utilisateur ne revoit que les changements de sa version précédente, avec un commentaire global facultatif de 500 caractères ;
- une reformulation force une revalidation ; un changement des choix ou du statut essentielle/facultative est signalé ;
- les brouillons incomplets sont purgés après 30 jours sans activité ;
- un unique rappel est prévu après 7 jours lorsque l’utilisateur a fourni une adresse e-mail ;
- les résultats agrégés s’ouvrent à partir de 7 participations exploitables ;
- les textes libres ne sont jamais publiés dans les agrégats.

### Pré-requis serveur du questionnaire

Le serveur PHP doit disposer de PDO avec le pilote SQLite et de Sodium. La base `sans-effort-survey.sqlite` et les secrets restent hors du webroot, dans le répertoire privé.

Secrets distincts recommandés :

- `SANS_EFFORT_IDENTITY_SECRET` : HMAC de l’adresse pour retrouver une participation sans indexer l’e-mail en clair ;
- `SANS_EFFORT_CONTACT_SECRET` : chiffrement de l’e-mail lorsque les rappels/reprises sont activés ;
- `SANS_EFFORT_AUTH_MAIL_SECRET` : signature des demandes serveur-à-serveur vers Le Potager Mailing.

L’endpoint Potager correspondant est `/mailing/sans-effort-auth-api.php`. Le même secret doit y être exposé sous `LEPOTAGER_SANS_EFFORT_AUTH_SECRET`.

La maintenance se lance côté serveur avec `php scripts/survey-maintenance.php`, typiquement une fois par jour. Elle envoie au maximum un rappel, purge les brouillons inactifs de plus de 30 jours et nettoie les OTP/sessions expirés.

Le code OTP est valable 15 minutes et une seule fois. Une session validée reste valable une heure et est restaurée localement tant qu’elle n’a pas expiré.
