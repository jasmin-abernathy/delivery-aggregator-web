# Garde-fous juridiques et produit

Dernière revue de principe : 29 septembre 2026.

Ce document ne remplace pas un avis juridique. Il sert de contrat produit interne pour éviter d’introduire par commodité technique un fonctionnement incompatible avec les conditions des plateformes ou les règles applicables.

## Autorisé / visé pour le MVP

- Comparer des données obtenues légalement et dont la réutilisation est permise.
- Permettre à l’utilisateur de déclarer qu’il dispose d’un abonnement de plateforme afin de personnaliser l’interface.
- Présenter des estimations clairement identifiées comme telles.
- Rediriger vers la page ou l’application officielle pour finaliser la commande.
- Utiliser les noms des services pour les identifier de façon descriptive, sans suggérer de partenariat.
- Ajouter ultérieurement des intégrations officielles si les droits/API/accords correspondants sont obtenus.

## À ne pas implémenter sans accord explicite adapté

- Scraping automatisé des interfaces grand public des services comparés.
- Collecte des identifiants, mots de passe, cookies, sessions ou jetons des comptes tiers.
- Automatisation de connexion ou de commande dans les comptes utilisateurs.
- Réutilisation d’un avantage d’abonnement dans un checkout tiers comme s’il était officiellement reconnu.
- Copie fidèle de l’interface, des textes, illustrations, logos ou assets propriétaires.
- Présentation laissant croire que le service est affilié, certifié ou partenaire d’une plateforme si ce n’est pas le cas.

## Transparence du comparateur

L’interface devra afficher de façon intelligible :

- la source et la fraîcheur des informations importantes ;
- ce qui est démonstration, confirmé, estimé ou inconnu ;
- les principaux critères de classement ;
- la présence éventuelle d’un référencement rémunéré ;
- le fait que le prix final, les frais et les avantages d’abonnement sont confirmés sur la plateforme officielle lorsque le checkout y est effectué.

Les catégories « Repas », « Courses » et « Anti-gaspi » ainsi que les modes « Livraison » et « Retrait » décrivent uniquement le type d’offre et son mode de récupération ; ils ne constituent pas une promesse de disponibilité locale.

## Données personnelles

Au début, privilégier le stockage local pour les préférences non sensibles. Si des comptes sont ajoutés plus tard : minimisation, finalités claires, sécurité, durées de conservation limitées et suppression/export devront être prévus dès la conception.

## Paiement

Le MVP ne collecte pas le paiement des commandes. Si un paiement marketplace est ajouté ultérieurement, l’architecture devra passer par un prestataire de services de paiement adapté et faire l’objet d’une analyse réglementaire dédiée.


## Questionnaire de test — minimisation et conservation

Le questionnaire distingue trois usages qui ne doivent pas être confondus :

1. réponses au test produit ;
2. reprise/authentification facultative par e-mail ;
3. inscription volontaire à la liste de test Sans Effort.

L’inscription à la liste de test reste un consentement séparé. Fournir une adresse uniquement pour reprendre une participation ou recevoir le rappel ne vaut pas inscription à cette liste.

Les participations incomplètes ne sont utilisées dans aucune analyse. Elles sont conservées au maximum 30 jours après la dernière activité, puis supprimées avec les données techniques associées. Un seul rappel peut être envoyé avant cette purge lorsqu’une adresse e-mail a été fournie.

L’adresse de reprise n’est pas utilisée comme clé en clair dans la base d’enquête : une clé HMAC sert à la recherche et la valeur nécessaire aux envois est chiffrée. Les codes temporaires sont hashés, valables 15 minutes et à usage unique ; les sessions durent une heure.

Les résultats affichés aux participants sont agrégés, ne contiennent pas les réponses textuelles libres, n’établissent aucun classement entre personnes et ne deviennent disponibles qu’à partir d’un seuil minimal de sept participations exploitables.

Avant mise en production, la durée de conservation des participations complètes doit encore être formalisée dans l’information remise aux testeurs ; le seuil de 30 jours ne concerne ici que les brouillons incomplets.
