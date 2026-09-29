# Architecture cible — MVP web

## 1. Stratégie générale

Le projet commence comme site web responsive, puis pourra devenir PWA et enfin application mobile sans jeter le modèle métier.

Le frontend ne doit jamais dépendre directement d’une structure de données propre à Uber Eats, Deliveroo, Too Good To Go, Le Fourgon ou à un autre service. Toute donnée externe passe par un adaptateur et est normalisée.

## 2. Modèle normalisé minimal

### Établissement / service

- `id` interne stable ;
- nom ;
- adresse / zone ;
- coordonnées si disponibles légalement ;
- catégories descriptives ;
- image autorisée ou image interne ;
- plateformes disponibles.

Le modèle doit pouvoir représenter aussi bien un restaurant qu’un commerce, une offre anti-gaspi ou un service de courses.

### Offre par plateforme

- plateforme ;
- identifiant externe si usage autorisé ;
- URL / deep link officiel ;
- mode de récupération : `delivery` ou `pickup` ;
- une ou plusieurs catégories d’offre : `meal`, `grocery`, `anti-waste` ;
- prix ou fourchette connue ;
- frais de livraison/retrait connus ;
- frais de service connus ;
- délai estimé ;
- minimum de commande ;
- horodatage de fraîcheur ;
- origine de la donnée ;
- niveau de confiance : `demo`, `confirmed`, `estimated`, `unknown`.

La plateforme, le mode et le type d’offre sont trois dimensions indépendantes. Par exemple, Uber Eats et Deliveroo peuvent porter des offres de repas ou de courses ; une offre peut être livrée ou retirée lorsque le service concerné le permet. Too Good To Go est modélisé dans le prototype principalement comme retrait anti-gaspi. Le Fourgon est modélisé comme courses livrées.

### Préférences utilisateur

Au MVP, stocker localement dans le navigateur :

- plateformes utilisées ;
- déclaration Uber One oui/non ;
- déclaration Deliveroo Plus oui/non ;
- favoris ;
- filtres habituels si cette persistance devient utile.

Aucune donnée d’authentification des plateformes tierces n’est collectée.

## 3. Sources autorisées

Chaque source de données devra déclarer son type :

- `manual` : saisie interne ou partenaire ;
- `merchant_feed` : flux fourni par l’établissement ;
- `official_api` : API officiellement accessible pour l’usage concerné ;
- `partner_api` : intégration contractuelle ;
- `public_licensed_data` : source publique réutilisable avec licence compatible.

Il n’existe volontairement aucun adaptateur `scraper`.

## 4. Flux MVP

1. L’utilisateur saisit une ville, un quartier, un établissement, un service ou une catégorie.
2. Le site affiche les entrées connues correspondant à la recherche.
3. L’utilisateur peut filtrer indépendamment la plateforme, le mode `Livraison` / `Retrait` et le type `Repas` / `Courses` / `Anti-gaspi`.
4. Les offres disponibles sont comparées sans fabriquer de valeur manquante.
5. Les abonnements déclarés restent informatifs tant qu’aucune donnée autorisée ne permet d’appliquer un avantage réel.
6. Le site affiche clairement ce qui est démonstration, confirmé, estimé ou inconnu.
7. Le bouton Commander ouvre uniquement une URL officielle HTTPS vérifiée et autorisée.
8. Le prix final et les avantages éventuels sont confirmés dans le parcours officiel du service.

## 5. Backend futur

Quand les premières données réelles seront disponibles, ajouter un backend léger derrière une API interne, par exemple :

- `GET /api/restaurants` ou une ressource plus générique si le périmètre courses est conservé ;
- `GET /api/restaurants/{id}` ;
- `GET /api/offers?restaurant={id}` ;
- `POST /api/favorites`.

Avant de figer l’API, renommer éventuellement la ressource `restaurants` en `merchants`/`places` pour refléter les commerces et services non-restaurants.

Les clés API et secrets restent hors du webroot et ne sont jamais commités.

## 6. Préparation de l’application mobile

Le futur client mobile devra consommer la même API et le même modèle normalisé. Les règles de comparaison, de classement et de transparence doivent être documentées indépendamment de l’interface web.
