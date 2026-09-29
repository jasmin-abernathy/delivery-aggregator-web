# Garde-fous produit et données

Revue de principe : 29 septembre 2026. Ce document est une règle de conception, pas un avis juridique.

## Fonctionnement de la version actuelle

- L’utilisateur relève lui-même dans Uber Eats et Deliveroo les prix, délais et frais affichés, puis les saisit dans Sans Effort.
- L’application ne scrape pas les interfaces, n’automatise pas la connexion et ne lit ni mot de passe, cookie, session ou jeton tiers.
- Les relevés sont conservés localement dans le navigateur; aucun compte ni serveur n’est requis.
- Une saisie est présentée comme une observation utilisateur, pas comme une donnée certifiée par la plateforme.
- Le prix, les frais, la disponibilité et les avantages d’abonnement sont à confirmer dans l’application officielle avant paiement.
- Les liens sortants ne peuvent viser que les domaines HTTPS officiels Uber Eats ou Deliveroo.

## Évolution des sources

Toute intégration automatique doit être autorisée par les conditions et l’accord applicables à l’usage précis, y compris la comparaison avec des concurrents. L’existence d’une API, d’un accès partenaire ou d’un flux marchand ne vaut pas à elle seule autorisation de réutiliser les données dans un comparateur consommateur. Aucun accès ne sera contourné ni simulé avec des identifiants d’utilisateur.

## Confidentialité

Les données du formulaire sont enregistrées dans le stockage local du navigateur et ne sont pas transmises au projet. L’utilisateur peut supprimer chaque observation depuis l’interface ou les données du site depuis son navigateur. Ne pas ajouter de données personnelles dans les noms ou notes.

## Présentation

Sans Effort est un projet indépendant. Les noms Uber Eats et Deliveroo sont employés pour identifier les plateformes. Aucun partenariat, affiliation ou validation par ces entreprises n’est revendiqué.
