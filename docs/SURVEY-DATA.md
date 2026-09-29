# Données du questionnaire Sans Effort

## Règles fonctionnelles

- Une réponse incomplète est un brouillon : elle peut être envoyée et reprise, mais n’est jamais analysée.
- Toutes les questions essentielles complétées rendent la participation exploitable automatiquement.
- Tant qu’elle n’a jamais été exploitable, les compléments modifient la même version.
- Après une première version exploitable, la version la plus récente remplace la précédente dans les statistiques ; les anciennes versions restent internes pour suivre l’évolution.
- L’utilisateur voit seulement ce qui a changé depuis sa version précédente et peut joindre un commentaire global facultatif de 500 caractères.
- Une reformulation est signalée pour inviter à vérifier la réponse, sans exiger de la modifier ou de la revalider. Des choix modifiés sont signalés sans revalidation forcée. Un changement essentielle/facultative est signalé et une nouvelle question essentielle non répondue est mise en évidence.
- Une question supprimée reste dans l’historique interne mais disparaît de l’interface et des comparaisons utilisateur.

## Séparation des données

La base SQLite du questionnaire contient les réponses, le versioning, les droits d’accès aux résultats, les OTP et les sessions. La liste e-mail de test reste gérée par Le Potager Mailing dans son stockage privé séparé.

Une adresse e-mail de reprise est facultative. La base conserve une clé HMAC pour l’identifier et une valeur chiffrée uniquement lorsque l’envoi d’un OTP ou du rappel est nécessaire.

## Accès aux résultats

- uniquement après participation exploitable ;
- même appareil : jeton local anonyme ;
- autre appareil : code e-mail à six chiffres, usage unique, 15 minutes ;
- session après validation : une heure, restaurable localement jusqu’à expiration ;
- page disponible seulement à partir de 7 participations exploitables ;
- seules les versions actives sont comptées ;
- aucune réponse textuelle libre n’est publiée.

## Maintenance

Commande : `php scripts/survey-maintenance.php`.

À exécuter quotidiennement côté serveur. Elle :

1. envoie au maximum un rappel aux brouillons ayant une adresse et au moins 7 jours d’inactivité ;
2. supprime les brouillons après 30 jours sans activité ;
3. supprime les OTP consommés/expirés et les sessions expirées.

## Déploiement

Pré-requis PHP : PDO + `pdo_sqlite`, Sodium, accès en écriture au répertoire privé.

Le serveur Sans Effort et Le Potager Mailing partagent uniquement le secret HMAC du connecteur d’authentification. Aucun secret ne doit être placé dans `public/`.
