<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute le contrôle journalier, les observations et la présence du personnel.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presence ADD observation LONGTEXT DEFAULT NULL, ADD validated_at DATETIME DEFAULT NULL, ADD validated_by_id INT DEFAULT NULL, ADD INDEX IDX_PRESENCE_VALIDATED_BY (validated_by_id)');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_PRESENCE_VALIDATED_BY FOREIGN KEY (validated_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE TABLE presence_personnel (id INT AUTO_INCREMENT NOT NULL, professeur_id INT DEFAULT NULL, personnel_id INT DEFAULT NULL, created_by INT DEFAULT NULL, date DATE NOT NULL, statut VARCHAR(30) NOT NULL, heure_arrivee TIME DEFAULT NULL, heure_depart TIME DEFAULT NULL, observation LONGTEXT DEFAULT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_PP_PROF (professeur_id), INDEX IDX_PP_USER (personnel_id), INDEX IDX_PP_CREATED_BY (created_by), UNIQUE INDEX UNIQ_PRESENCE_PERSONNEL_JOUR (professeur_id, personnel_id, date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE presence_personnel ADD CONSTRAINT FK_PP_PROF FOREIGN KEY (professeur_id) REFERENCES professeur (id) ON DELETE CASCADE, ADD CONSTRAINT FK_PP_USER FOREIGN KEY (personnel_id) REFERENCES `user` (id) ON DELETE CASCADE, ADD CONSTRAINT FK_PP_CREATED_BY FOREIGN KEY (created_by) REFERENCES `user` (id)');
    }
    public function down(Schema $schema): void { $this->throwIrreversibleMigrationException('Cette migration ajoute des historiques de présence.'); }
}
