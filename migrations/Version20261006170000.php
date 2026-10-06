<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261006170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sépare les horaires par classe/option et leurs cellules par jour et heure';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $this->abortIf(
            !$schemaManager->tablesExist(['horaire', 'creneau_horaire', 'classe', 'ecole', 'cours']),
            'Les tables requises pour convertir les horaires ne sont pas toutes présentes.'
        );
        $this->abortIf(
            $schemaManager->tablesExist(['horaire_legacy', 'heure', 'jour']),
            'Une ou plusieurs tables de destination existent déjà; migration arrêtée pour éviter toute perte de données.'
        );

        $invalidClasses = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
            FROM horaire h
            LEFT JOIN classe c ON c.id = h.classe_id
            WHERE c.id IS NULL OR c.ecole_id IS NULL'
        );
        if ($invalidClasses > 0) {
            throw new RuntimeException(sprintf(
                'Migration interrompue : %d horaire(s) ont une classe sans école valide.',
                $invalidClasses
            ));
        }

        $invalidDays = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM horaire
            WHERE jour NOT IN ('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi')"
        );
        if ($invalidDays > 0) {
            throw new RuntimeException(sprintf(
                'Migration interrompue : %d horaire(s) utilisent un jour non reconnu.',
                $invalidDays
            ));
        }

        $duplicateCells = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (
                SELECT classe_id, creneau_id, jour
                FROM horaire
                GROUP BY classe_id, creneau_id, jour
                HAVING COUNT(*) > 1
            ) duplicates'
        );
        if ($duplicateCells > 0) {
            throw new RuntimeException(sprintf(
                'Migration interrompue : %d créneau(x) contiennent des cellules dupliquées.',
                $duplicateCells
            ));
        }

        $unmappableCells = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
            FROM horaire ancien
            LEFT JOIN creneau_horaire heure ON heure.id = ancien.creneau_id
            LEFT JOIN cours ON cours.id = ancien.cours_id
            LEFT JOIN `option` o
                ON o.id = cours.option_id
               AND o.classe_id = ancien.classe_id
            WHERE heure.id IS NULL
               OR (ancien.cours_id IS NOT NULL AND cours.id IS NULL)
               OR (cours.option_id IS NOT NULL AND o.id IS NULL)'
        );
        if ($unmappableCells > 0) {
            throw new RuntimeException(sprintf(
                'Migration interrompue : %d cellule(s) utilisent un créneau, un cours ou une option introuvable.',
                $unmappableCells
            ));
        }

        $this->addSql(
            'ALTER TABLE horaire
            DROP FOREIGN KEY FK_BBC83DB67D0729A9,
            DROP FOREIGN KEY FK_BBC83DB67ECF78B0,
            DROP FOREIGN KEY FK_BBC83DB68F5EA509'
        );
        $this->addSql('RENAME TABLE horaire TO horaire_legacy, creneau_horaire TO heure');

        $this->addSql(
            'CREATE TABLE jour (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(20) NOT NULL,
                ordre INT NOT NULL,
                UNIQUE INDEX UNIQ_DA17D9C56C6E55B5 (nom),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE horaire (
                id INT AUTO_INCREMENT NOT NULL,
                classe_id INT NOT NULL,
                option_id INT DEFAULT NULL,
                ecole_id INT NOT NULL,
                INDEX IDX_BBC83DB68F5EA509 (classe_id),
                INDEX IDX_BBC83DB6A7C41D6F (option_id),
                INDEX IDX_BBC83DB677EF1B1E (ecole_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_BBC83DB68F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE,
                CONSTRAINT FK_BBC83DB6A7C41D6F FOREIGN KEY (option_id) REFERENCES `option` (id) ON DELETE CASCADE,
                CONSTRAINT FK_BBC83DB677EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE creneau_horaire (
                id INT AUTO_INCREMENT NOT NULL,
                horaire_id INT NOT NULL,
                jour_id INT NOT NULL,
                heure_id INT NOT NULL,
                cours_id INT DEFAULT NULL,
                professeur_id INT DEFAULT NULL,
                statut VARCHAR(50) DEFAULT NULL,
                heure_debut_reelle TIME DEFAULT NULL,
                heure_fin_reelle TIME DEFAULT NULL,
                observation LONGTEXT DEFAULT NULL,
                INDEX IDX_521E5DC258C54515 (horaire_id),
                INDEX IDX_521E5DC2220C6AD0 (jour_id),
                INDEX IDX_521E5DC2F2A733EB (heure_id),
                INDEX IDX_521E5DC27ECF78B0 (cours_id),
                INDEX IDX_521E5DC2BAB22EE9 (professeur_id),
                UNIQUE INDEX UNIQ_C8B1268329BAFFC1B6F1A3 (horaire_id, jour_id, heure_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_521E5DC258C54515 FOREIGN KEY (horaire_id) REFERENCES horaire (id) ON DELETE CASCADE,
                CONSTRAINT FK_521E5DC2220C6AD0 FOREIGN KEY (jour_id) REFERENCES jour (id),
                CONSTRAINT FK_521E5DC2F2A733EB FOREIGN KEY (heure_id) REFERENCES heure (id),
                CONSTRAINT FK_521E5DC27ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE SET NULL,
                CONSTRAINT FK_521E5DC2BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES professeur (id) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        foreach (['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'] as $index => $jour) {
            $this->addSql(
                'INSERT INTO jour (id, nom, ordre) VALUES (?, ?, ?)',
                [$index + 1, $jour, $index + 1],
                ['integer', 'string', 'integer']
            );
        }

        $this->addSql(
            'INSERT INTO horaire (classe_id, option_id, ecole_id)
            SELECT c.id, NULL, c.ecole_id
            FROM classe c
            WHERE c.ecole_id IS NOT NULL AND COALESCE(c.deleted, 0) = 0'
        );
        $this->addSql(
            'INSERT INTO horaire (classe_id, option_id, ecole_id)
            SELECT c.id, o.id, c.ecole_id
            FROM classe c
            INNER JOIN `option` o ON o.classe_id = c.id
            WHERE c.ecole_id IS NOT NULL
              AND COALESCE(c.deleted, 0) = 0
              AND COALESCE(o.deleted, 0) = 0'
        );

        $this->addSql(
            'INSERT INTO creneau_horaire (horaire_id, jour_id, heure_id)
            SELECT h.id, j.id, heure.id
            FROM horaire h
            CROSS JOIN jour j
            CROSS JOIN heure'
        );

        $this->addSql(
            'UPDATE creneau_horaire cellule
            INNER JOIN horaire h ON h.id = cellule.horaire_id
            INNER JOIN jour j ON j.id = cellule.jour_id
            INNER JOIN heure ON heure.id = cellule.heure_id
            INNER JOIN horaire_legacy ancien
                ON ancien.classe_id = h.classe_id
               AND ancien.creneau_id = heure.id
               AND ancien.jour = j.nom
            LEFT JOIN cours ON cours.id = ancien.cours_id
            SET cellule.cours_id = ancien.cours_id,
                cellule.professeur_id = cours.professeur_id,
                cellule.statut = ancien.statut,
                cellule.heure_debut_reelle = ancien.heure_debut_reelle,
                cellule.heure_fin_reelle = ancien.heure_fin_reelle,
                cellule.observation = ancien.observation
            WHERE h.option_id <=> cours.option_id'
        );

        $this->addSql('DROP TABLE horaire_legacy');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE horaire_legacy (
                id INT AUTO_INCREMENT NOT NULL,
                jour VARCHAR(50) NOT NULL,
                creneau_id INT NOT NULL,
                cours_id INT DEFAULT NULL,
                classe_id INT DEFAULT NULL,
                statut VARCHAR(50) DEFAULT NULL,
                heure_debut_reelle TIME DEFAULT NULL,
                heure_fin_reelle TIME DEFAULT NULL,
                observation LONGTEXT DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'INSERT INTO horaire_legacy
                (jour, creneau_id, cours_id, classe_id, statut, heure_debut_reelle, heure_fin_reelle, observation)
            SELECT j.nom, cellule.heure_id, cellule.cours_id, h.classe_id,
                   cellule.statut, cellule.heure_debut_reelle, cellule.heure_fin_reelle, cellule.observation
            FROM creneau_horaire cellule
            INNER JOIN horaire h ON h.id = cellule.horaire_id
            INNER JOIN jour j ON j.id = cellule.jour_id'
        );

        $this->addSql('DROP TABLE creneau_horaire');
        $this->addSql('DROP TABLE horaire');
        $this->addSql('RENAME TABLE heure TO creneau_horaire, horaire_legacy TO horaire');
        $this->addSql(
            'CREATE INDEX IDX_BBC83DB67ECF78B0 ON horaire (cours_id)'
        );
        $this->addSql(
            'CREATE INDEX IDX_BBC83DB68F5EA509 ON horaire (classe_id)'
        );
        $this->addSql(
            'ALTER TABLE horaire
            ADD CONSTRAINT FK_BBC83DB67ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id),
            ADD CONSTRAINT FK_BBC83DB68F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)'
        );
        $this->addSql('DROP TABLE jour');
    }
}
