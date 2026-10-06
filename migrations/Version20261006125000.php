<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261006125000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les créneaux horaires et migre les heures existantes sans perte de données';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['creneau_horaire'])) {
            $this->addSql(
                'CREATE TABLE creneau_horaire (
                    id INT AUTO_INCREMENT NOT NULL,
                    nom VARCHAR(100) NOT NULL,
                    heure_debut TIME NOT NULL,
                    heure_fin TIME NOT NULL,
                    ordre INT NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
        }

        $horaire = $schemaManager->introspectTable('horaire');

        if (!$horaire->hasColumn('creneau_id')) {
            $this->addSql('ALTER TABLE horaire ADD creneau_id INT DEFAULT NULL');
        }

        if ($horaire->hasColumn('heure_debut') && $horaire->hasColumn('heure_fin')) {
            $this->addSql(
                "INSERT INTO creneau_horaire (nom, heure_debut, heure_fin, ordre)
                SELECT DISTINCT
                    CONCAT('Créneau ', TIME_FORMAT(h.heure_debut, '%H:%i'), ' - ', TIME_FORMAT(h.heure_fin, '%H:%i')),
                    h.heure_debut,
                    h.heure_fin,
                    0
                FROM horaire h
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM creneau_horaire c
                    WHERE c.heure_debut = h.heure_debut
                      AND c.heure_fin = h.heure_fin
                )
                ORDER BY h.heure_debut, h.heure_fin"
            );
            $this->addSql(
                'UPDATE creneau_horaire SET ordre = id WHERE ordre = 0'
            );
            $this->addSql(
                'UPDATE horaire h
                INNER JOIN creneau_horaire c
                    ON c.heure_debut = h.heure_debut
                   AND c.heure_fin = h.heure_fin
                SET h.creneau_id = c.id
                WHERE h.creneau_id IS NULL'
            );
        }

        $professeur = $schemaManager->introspectTable('professeur');
        $missingRequiredValues = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM professeur
            WHERE ecole_id IS NULL OR annee_scolaire_id IS NULL OR section_id IS NULL'
        );

        if ($missingRequiredValues > 0) {
            throw new RuntimeException(sprintf(
                'Migration interrompue : %d professeur(s) n’ont pas encore une école, une année scolaire et une section.',
                $missingRequiredValues
            ));
        }

        foreach ([
            'FK_17A5529977EF1B1E',
            'FK_PROFESSEUR_ANNEE',
            'FK_PROFESSEUR_SECTION',
        ] as $foreignKey) {
            if ($professeur->hasForeignKey($foreignKey)) {
                $this->addSql(sprintf(
                    'ALTER TABLE professeur DROP FOREIGN KEY %s',
                    $foreignKey
                ));
            }
        }

        foreach (['ecole_id', 'annee_scolaire_id', 'section_id'] as $column) {
            if ($professeur->hasColumn($column) && $professeur->getColumn($column)->getNotnull() === false) {
                $this->addSql(sprintf('ALTER TABLE professeur MODIFY %s INT NOT NULL', $column));
            }
        }

        $this->normalizeIndex($professeur, 'IDX_PROFESSEUR_ANNEE', 'IDX_17A552999331C741');
        $this->normalizeIndex($professeur, 'IDX_PROFESSEUR_SECTION', 'IDX_17A55299D823E37A');
        $this->normalizeIndex($professeur, 'UNIQ_PROFESSEUR_USER', 'UNIQ_17A55299A76ED395');

        foreach ([
            ['FK_17A5529977EF1B1E', 'ecole_id', 'ecole'],
            ['FK_PROFESSEUR_ANNEE', 'annee_scolaire_id', 'annee_scolaire'],
            ['FK_PROFESSEUR_SECTION', 'section_id', 'section'],
        ] as [$foreignKey, $column, $referencedTable]) {
            $this->addSql(sprintf(
                'ALTER TABLE professeur ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id)',
                $foreignKey,
                $column,
                $referencedTable
            ));
        }

        if ($horaire->hasForeignKey('FK_BBC83DB67D0729A9')) {
            $this->addSql('ALTER TABLE horaire DROP FOREIGN KEY FK_BBC83DB67D0729A9');
        }

        $this->addSql('ALTER TABLE horaire MODIFY creneau_id INT NOT NULL');

        if (!$horaire->hasIndex('IDX_BBC83DB67D0729A9')) {
            $this->addSql('CREATE INDEX IDX_BBC83DB67D0729A9 ON horaire (creneau_id)');
        }

        $this->addSql(
            'ALTER TABLE horaire ADD CONSTRAINT FK_BBC83DB67D0729A9 FOREIGN KEY (creneau_id) REFERENCES creneau_horaire (id)'
        );

        if ($horaire->hasColumn('heure_debut')) {
            $this->addSql('ALTER TABLE horaire DROP heure_debut');
        }
        if ($horaire->hasColumn('heure_fin')) {
            $this->addSql('ALTER TABLE horaire DROP heure_fin');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $horaire = $schemaManager->introspectTable('horaire');

        if (!$horaire->hasColumn('heure_debut')) {
            $this->addSql('ALTER TABLE horaire ADD heure_debut TIME DEFAULT NULL');
        }
        if (!$horaire->hasColumn('heure_fin')) {
            $this->addSql('ALTER TABLE horaire ADD heure_fin TIME DEFAULT NULL');
        }

        $this->addSql(
            'UPDATE horaire h
            INNER JOIN creneau_horaire c ON c.id = h.creneau_id
            SET h.heure_debut = c.heure_debut, h.heure_fin = c.heure_fin'
        );
        $this->addSql('ALTER TABLE horaire MODIFY heure_debut TIME NOT NULL');
        $this->addSql('ALTER TABLE horaire MODIFY heure_fin TIME NOT NULL');
        $this->addSql('ALTER TABLE horaire DROP FOREIGN KEY FK_BBC83DB67D0729A9');
        $this->addSql('DROP INDEX IDX_BBC83DB67D0729A9 ON horaire');
        $this->addSql('ALTER TABLE horaire DROP creneau_id');
        $this->addSql('DROP TABLE creneau_horaire');

        $professeur = $schemaManager->introspectTable('professeur');
        foreach ([
            'FK_17A5529977EF1B1E',
            'FK_PROFESSEUR_ANNEE',
            'FK_PROFESSEUR_SECTION',
        ] as $foreignKey) {
            if ($professeur->hasForeignKey($foreignKey)) {
                $this->addSql(sprintf(
                    'ALTER TABLE professeur DROP FOREIGN KEY %s',
                    $foreignKey
                ));
            }
        }

        foreach ([
            ['IDX_17A552999331C741', 'IDX_PROFESSEUR_ANNEE', 'annee_scolaire_id'],
            ['IDX_17A55299D823E37A', 'IDX_PROFESSEUR_SECTION', 'section_id'],
            ['UNIQ_17A55299A76ED395', 'UNIQ_PROFESSEUR_USER', 'user_id'],
        ] as [$current, $previous, $column]) {
            $professeur = $schemaManager->introspectTable('professeur');
            if ($professeur->hasIndex($current)) {
                $this->addSql(sprintf('ALTER TABLE professeur RENAME INDEX %s TO %s', $current, $previous));
            }
            if ($column !== 'user_id' && $professeur->getColumn($column)->getNotnull()) {
                $this->addSql(sprintf('ALTER TABLE professeur MODIFY %s INT DEFAULT NULL', $column));
            }
        }

        foreach ([
            ['FK_17A5529977EF1B1E', 'ecole_id', 'ecole'],
            ['FK_PROFESSEUR_ANNEE', 'annee_scolaire_id', 'annee_scolaire'],
            ['FK_PROFESSEUR_SECTION', 'section_id', 'section'],
        ] as [$foreignKey, $column, $referencedTable]) {
            $this->addSql(sprintf(
                'ALTER TABLE professeur ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id)',
                $foreignKey,
                $column,
                $referencedTable
            ));
        }
    }

    private function normalizeIndex(
        \Doctrine\DBAL\Schema\Table $table,
        string $oldName,
        string $newName
    ): void {
        if ($table->hasIndex($oldName) && !$table->hasIndex($newName)) {
            $this->addSql(sprintf(
                'ALTER TABLE professeur RENAME INDEX %s TO %s',
                $oldName,
                $newName
            ));
        }
    }
}
