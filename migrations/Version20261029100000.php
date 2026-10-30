<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la configuration des bulletins, les périodes et les évaluations.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $expectedTables = ['modele_bulletin', 'configuration_bulletin', 'evaluation'];
        $existingTables = $schemaManager->listTableNames();
        $existingBulletinTables = array_intersect($expectedTables, $existingTables);
        if ($existingBulletinTables === $expectedTables) {
            return;
        }
        if ($existingBulletinTables !== []) {
            throw new \RuntimeException(sprintf(
                'Le schéma de bulletin est partiel (%s). Aucune modification n’a été appliquée ; régularisez les tables avant de relancer la migration.',
                implode(', ', $existingBulletinTables)
            ));
        }

        $this->addSql('CREATE TABLE modele_bulletin (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE configuration_bulletin (id INT AUTO_INCREMENT NOT NULL, modele_bulletin_id INT DEFAULT NULL, ecole_id INT DEFAULT NULL, annee_scolaire_id INT DEFAULT NULL, classe_id INT DEFAULT NULL, options_id INT DEFAULT NULL, nom VARCHAR(150) NOT NULL, statut VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CONFIGURATION_MODELE (modele_bulletin_id), INDEX IDX_CONFIGURATION_ECOLE (ecole_id), INDEX IDX_CONFIGURATION_ANNEE (annee_scolaire_id), INDEX IDX_CONFIGURATION_CLASSE (classe_id), INDEX IDX_CONFIGURATION_OPTION (options_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, cours_id INT NOT NULL, configuration_bulletin_id INT NOT NULL, nom VARCHAR(100) NOT NULL, type VARCHAR(150) NOT NULL, bareme INT NOT NULL, coefficient INT NOT NULL, date_evaluation DATE DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EVALUATION_COURS (cours_id), INDEX IDX_EVALUATION_CONFIGURATION (configuration_bulletin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE configuration_bulletin ADD CONSTRAINT FK_CONFIGURATION_MODELE FOREIGN KEY (modele_bulletin_id) REFERENCES modele_bulletin (id) ON DELETE SET NULL, ADD CONSTRAINT FK_CONFIGURATION_ECOLE FOREIGN KEY (ecole_id) REFERENCES ecole (id) ON DELETE SET NULL, ADD CONSTRAINT FK_CONFIGURATION_ANNEE FOREIGN KEY (annee_scolaire_id) REFERENCES annee_scolaire (id) ON DELETE SET NULL, ADD CONSTRAINT FK_CONFIGURATION_CLASSE FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE SET NULL, ADD CONSTRAINT FK_CONFIGURATION_OPTION FOREIGN KEY (options_id) REFERENCES `option` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_EVALUATION_COURS FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE, ADD CONSTRAINT FK_EVALUATION_CONFIGURATION FOREIGN KEY (configuration_bulletin_id) REFERENCES configuration_bulletin (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Cette migration initialise les données de configuration du bulletin.');
    }
}
