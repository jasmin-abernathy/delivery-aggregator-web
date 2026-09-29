# Architecture de la première version utilisable

## Parcours actuel

Sans Effort est un comparateur personnel local. L’utilisateur consulte lui-même les offres dans Uber Eats et Deliveroo, puis saisit les totaux et délais visibles dans leurs parcours officiels avant paiement. Le navigateur regroupe les saisies qui ont le même nom de comparaison et le même commerce.

Le comparateur ne lit pas les applications ou leurs pages, ne se connecte pas aux comptes et ne reçoit aucune donnée de serveur. Les entrées sont sauvegardées dans `localStorage`, jusqu’à 100 par navigateur. Les valeurs restent celles saisies; elles ne sont ni estimées ni recalculées. Pour comparer, une offre de chaque plateforme doit avoir été relevée au cours des deux dernières heures.

## Modèle d’un relevé

- identifiant local aléatoire ;
- nom donné à la comparaison ;
- nom du commerce ;
- plateforme (`uber-eats` ou `deliveroo`) ;
- total affiché avant paiement, en euros ;
- délai annoncé, facultatif ;
- détail des frais/promotions et contenu du panier, facultatifs ;
- lien HTTPS officiel de l’offre, facultatif ;
- heure d’observation.

La saisie ne prouve pas que deux paniers sont identiques. L’interface invite l’utilisateur à vérifier le contenu, l’adresse, les frais et les conditions d’abonnement dans les applications. Aucun prix final garanti n’est annoncé.

## Stockage et sécurité

Pas de compte ni d’API serveur dans cette version. Les données ne quittent pas le navigateur et ne sont pas synchronisées entre appareils. Le code valide les domaines des liens optionnels et échappe les valeurs saisies avant leur rendu HTML.

## Intégrations automatiques éventuelles

Une API, un flux marchand ou un autre accès ne peut être ajouté que si sa documentation et les autorisations couvrent explicitement l’usage du comparateur, y compris l’affichage avec des concurrents. Aucun scraping, contournement de l’application ou collecte d’identifiants tiers n’est prévu. En l’absence d’autorisation, la saisie manuelle reste la seule source.

## Suites possibles

1. Tester la saisie et la comparaison sur un panier réel.
2. Corriger l’ergonomie et vérifier le besoin de synchronisation.
3. Demander un accès écrit aux plateformes si l’automatisation reste nécessaire.
4. Décider ensuite si un backend ou une synchronisation multi-appareil apporte une valeur suffisante.
