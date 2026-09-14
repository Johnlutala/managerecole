<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914213500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the student registration table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE inscription_eleve (id INT AUTO_INCREMENT NOT NULL, etablissement_id INT DEFAULT NULL, annee_scolaire_id INT DEFAULT NULL, section_id INT DEFAULT NULL, classe_id INT DEFAULT NULL, options_id INT DEFAULT NULL, created_by INT DEFAULT NULL, nom VARCHAR(50) NOT NULL, postnom VARCHAR(50) NOT NULL, prenom VARCHAR(50) DEFAULT NULL, sexe VARCHAR(20) NOT NULL, date_naissance DATE NOT NULL, lieu_naissance VARCHAR(50) NOT NULL, nationalite VARCHAR(50) DEFAULT NULL, adresse VARCHAR(255) NOT NULL, telephone VARCHAR(50) DEFAULT NULL, reference VARCHAR(50) DEFAULT NULL, type_inscription VARCHAR(50) DEFAULT NULL, statut VARCHAR(50) DEFAULT NULL, nom_parent VARCHAR(50) NOT NULL, postnom_parent VARCHAR(50) DEFAULT NULL, prenom_parent VARCHAR(50) DEFAULT NULL, telephone_parent VARCHAR(50) DEFAULT NULL, email_parent VARCHAR(50) DEFAULT NULL, adresse_parent VARCHAR(255) DEFAULT NULL, profession_parent VARCHAR(255) DEFAULT NULL, lien_parental VARCHAR(50) NOT NULL, date_validation DATETIME DEFAULT NULL, motif_rejet VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_BEE851BAFF631228 (etablissement_id), INDEX IDX_BEE851BA9331C741 (annee_scolaire_id), INDEX IDX_BEE851BAD823E37A (section_id), INDEX IDX_BEE851BA8F5EA509 (classe_id), INDEX IDX_BEE851BA3ADB05F1 (options_id), INDEX IDX_BEE851BADE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BAFF631228 FOREIGN KEY (etablissement_id) REFERENCES ecole (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BA9331C741 FOREIGN KEY (annee_scolaire_id) REFERENCES annee_scolaire (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BAD823E37A FOREIGN KEY (section_id) REFERENCES section (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BA8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BA3ADB05F1 FOREIGN KEY (options_id) REFERENCES `option` (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BADE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inscription_eleve');
    }
}
