# Migrations Doctrine

Le dossier `migrations/` est la source de vérité pour l'historique du schéma. La migration `Version20261007105635` crée les tables sur une base vide. Si elle trouve déjà toutes les tables applicatives attendues, elle les adopte sans les recréer ; si elle ne trouve qu'une partie des tables, elle s'arrête avec une erreur explicite.

## Base locale vide

Vérifier d'abord que `DATABASE_URL` dans `.env.local` pointe vers la base locale voulue. Créer la base si elle n'existe pas, puis appliquer les migrations :

```powershell
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

La migration crée les tables applicatives et Doctrine enregistre son exécution dans `doctrine_migration_versions`. Ne pas relancer `doctrine:schema:update --force` : l'historique doit être géré par les migrations.

## Base de production déjà existante

Faire une sauvegarde, déployer le code et les migrations, puis appliquer les migrations en production :

```sh
APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=prod php bin/console doctrine:schema:validate
APP_ENV=prod php bin/console doctrine:schema:update --dump-sql
```

La migration `Version20261007131500` aligne la nullabilité de toutes les colonnes relevées par `doctrine:schema:update --dump-sql` en production, sans supprimer ni recréer de tables. Elle remplace également les rôles `NULL` par un tableau JSON vide avant de rendre `user.roles` obligatoire. Si la migration initiale signale un schéma partiel, arrêter le déploiement : il faut d'abord préparer une migration adaptée aux tables manquantes.

## Migrations suivantes

Après chaque changement d'entité, générer une migration, relire son SQL (notamment les `DROP`, renommages, contraintes et modifications non nulles), puis la tester sur une base locale :

```sh
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```

Ne jamais supprimer des migrations déjà déployées. Pour corriger un schéma, ajouter une nouvelle migration. La migration de base ne peut pas être annulée, afin d'empêcher un rollback accidentel de supprimer toutes les tables et données.
