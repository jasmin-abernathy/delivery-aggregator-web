# Architecture cible — MVP web

## 1. Stratégie générale

Le projet commence comme site web responsive, puis pourra devenir PWA et enfin application mobile sans jeter le modèle métier.

Le frontend ne doit jamais dépendre directement d’une structure de données propre à Uber Eats ou Deliveroo. Toute donnée externe passe par un adaptateur et est normalisée.

## 2. Modèle normalisé minimal

### Établissement

- `id` interne stable
- nom
- adresse / zone
- coordonnées si disponibles légalement
- catégories / cuisines
- image autorisée ou image interne
- plateformes disponibles

### Offre par plateforme

- plateforme
- identifiant externe si usage autorisé
- URL / deep link officiel
- prix ou fourchette connue
- frais de livraison connus
- frais de service connus
- délai estimé
- minimum de commande
- horodatage de fraîcheur
- origine de la donnée
- niveau de confiance : `confirmed`, `estimated`, `unknown`

### Préférences utilisateur

Au MVP, stocker localement dans le navigateur :

- plateformes utilisées ;
- déclaration Uber One oui/non ;
- déclaration Deliveroo Plus oui/non ;
- favoris ;
- filtres habituels.

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

1. L’utilisateur saisit une adresse ou une zone.
2. Le site affiche les établissements connus dans cette zone.
3. Les offres disponibles sont comparées.
4. Les abonnements déclarés servent uniquement à personnaliser le classement ou l’explication des avantages potentiels.
5. Le site affiche clairement ce qui est confirmé et ce qui est estimé.
6. Le bouton Commander ouvre la plateforme officielle choisie.
7. Le prix final et les avantages d’abonnement sont confirmés dans le checkout officiel.

## 5. Backend futur

Quand les premières données réelles seront disponibles, ajouter un backend léger derrière une API interne, par exemple :

- `GET /api/restaurants`
- `GET /api/restaurants/{id}`
- `GET /api/offers?restaurant={id}`
- `POST /api/favorites`

Les clés API et secrets restent hors du webroot et ne sont jamais commités.

## 6. Préparation de l’application mobile

Le futur client mobile devra consommer la même API et le même modèle normalisé. Les règles de comparaison, de classement et de transparence doivent être documentées indépendamment de l’interface web.
