# Sans Effort

Comparateur personnel pour relever et comparer les offres réellement affichées dans Uber Eats et Deliveroo.

## Ce que le prototype fait

- ouvre les sites officiels pour consulter les offres dans les applications ou le navigateur ;
- enregistre manuellement le total affiché avant paiement, le délai, les frais/promotions et le contenu du panier ;
- regroupe les relevés par nom de comparaison, commerce, mode (livraison/retrait) et secteur facultatif ;
- met en évidence le relevé le moins cher lorsque les deux plateformes ont été relevées dans les deux dernières heures ;
- conserve jusqu’à 100 relevés dans le `localStorage` de l’appareil, avec filtre et suppression individuelle.

Le prix saisi vient de l’utilisateur et n’est pas vérifié par Sans Effort. Les relevés deviennent obsolètes pour la comparaison après deux heures. Le prix final, les frais et les avantages d’abonnement doivent toujours être confirmés dans l’application avant la commande.

## Ce que le prototype ne fait pas

Le prototype ne lit pas les applications, ne récupère pas automatiquement leurs prix, n’utilise pas d’identifiants/cookies de comptes et ne passe aucune commande. Il n’affiche ni restaurant ni tarif de démonstration. Il n’existe pas d’API publique de découverte consommateur documentée pour alimenter ici un comparateur ; une automatisation demanderait une autorisation adaptée des plateformes.

## Lancer en local

Aucune dépendance ni étape de build n’est nécessaire. Depuis la racine du dépôt :

```bash
python3 -m http.server 8080 --directory public
```

Ouvre ensuite `http://127.0.0.1:8080/`. Le formulaire et la comparaison sont utilisables sans backend. Les données restent dans le navigateur actuel ; elles ne sont pas synchronisées entre appareils.

## Confidentialité et déploiement

Les relevés demeurent dans le stockage local du navigateur. Effacer les données du site dans le navigateur les supprime. N’enregistre pas de données personnelles dans les notes. Le prototype ne requiert aucun secret serveur ni permission de compte tiers.

La branche de travail n’est pas fusionnée ni déployée par ce changement. Vérifier l’affichage mobile et desktop, la politique de confidentialité du site hôte et les liens officiels avant publication.

## Questionnaire

Les anciens fichiers de questionnaire restent hors du parcours principal et en pause : tant que la comparaison de données réelles ne sera pas testable, les retours sur une maquette fictive ne sont pas considérés utiles pour orienter le produit.
