# Garde-fous juridiques et produit

Dernière revue de principe : 4 septembre 2026.

Ce document ne remplace pas un avis juridique. Il sert de contrat produit interne pour éviter d’introduire par commodité technique un fonctionnement incompatible avec les conditions des plateformes ou les règles applicables.

## Autorisé / visé pour le MVP

- Comparer des données obtenues légalement et dont la réutilisation est permise.
- Permettre à l’utilisateur de déclarer qu’il dispose d’un abonnement de plateforme afin de personnaliser l’interface.
- Présenter des estimations clairement identifiées comme telles.
- Rediriger vers la page ou l’application officielle pour finaliser la commande.
- Utiliser les noms des services pour les identifier de façon descriptive, sans suggérer de partenariat.
- Ajouter ultérieurement des intégrations officielles si les droits/API/accords correspondants sont obtenus.

## À ne pas implémenter sans accord explicite adapté

- Scraping automatisé des interfaces grand public Uber Eats ou Deliveroo.
- Collecte des identifiants ou mots de passe des comptes tiers.
- Automatisation de connexion ou de commande dans les comptes utilisateurs.
- Réutilisation d’un avantage Uber One / Deliveroo Plus dans un checkout tiers comme s’il était officiellement reconnu.
- Copie fidèle de l’interface, des textes, illustrations, logos ou assets propriétaires.
- Présentation laissant croire que le service est affilié, certifié ou partenaire des plateformes si ce n’est pas le cas.

## Transparence du comparateur

L’interface devra afficher de façon intelligible :

- la source et la fraîcheur des informations importantes ;
- ce qui est confirmé, estimé ou inconnu ;
- les principaux critères de classement ;
- la présence éventuelle d’un référencement rémunéré ;
- le fait que le prix final, les frais et les avantages d’abonnement sont confirmés sur la plateforme officielle lorsque le checkout y est effectué.

## Données personnelles

Au début, privilégier le stockage local pour les préférences non sensibles. Si des comptes sont ajoutés plus tard : minimisation, finalités claires, sécurité, durées de conservation limitées et suppression/export devront être prévus dès la conception.

## Paiement

Le MVP ne collecte pas le paiement des commandes. Si un paiement marketplace est ajouté ultérieurement, l’architecture devra passer par un prestataire de services de paiement adapté et faire l’objet d’une analyse réglementaire dédiée.
