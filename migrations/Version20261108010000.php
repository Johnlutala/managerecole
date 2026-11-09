<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create teacher lesson preparations with review workflow and per-course numbering.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE preparation_cours (
            id INT AUTO_INCREMENT NOT NULL,
            professeur_id INT NOT NULL,
            cours_id INT NOT NULL,
            traitee_par_id INT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            numero INT NOT NULL,
            date_cours DATE NOT NULL,
            jour VARCHAR(20) NOT NULL,
            heure_debut TIME DEFAULT NULL,
            heure_fin TIME DEFAULT NULL,
            sujet VARCHAR(255) NOT NULL,
            reference LONGTEXT DEFAULT NULL,
            objectif_general LONGTEXT DEFAULT NULL,
            objectifs_specifiques LONGTEXT DEFAULT NULL,
            matiere LONGTEXT DEFAULT NULL,
            methode LONGTEXT DEFAULT NULL,
            materiel_didactique LONGTEXT DEFAULT NULL,
            activites_initiales_enseignant LONGTEXT DEFAULT NULL,
            activites_initiales_eleves LONGTEXT DEFAULT NULL,
            activites_principales_enseignant LONGTEXT DEFAULT NULL,
            activites_principales_eleves LONGTEXT DEFAULT NULL,
            activites_finales_enseignant LONGTEXT DEFAULT NULL,
            activites_finales_eleves LONGTEXT DEFAULT NULL,
            evaluation LONGTEXT DEFAULT NULL,
            devoir LONGTEXT DEFAULT NULL,
            statut VARCHAR(20) NOT NULL,
            commentaire_direction LONGTEXT DEFAULT NULL,
            soumise_le DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            traitee_le DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            code VARCHAR(50) DEFAULT NULL,
            enabled TINYINT(1) DEFAULT NULL,
            deleted TINYINT(1) DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE INDEX uniq_preparation_prof_cours_numero (professeur_id, cours_id, numero),
            UNIQUE INDEX UNIQ_FF15E2C077153098 (code),
            INDEX IDX_FF15E2C0BAB22EE9 (professeur_id),
            INDEX IDX_FF15E2C07ECF78B0 (cours_id),
            INDEX IDX_FF15E2C03338F50D (traitee_par_id),
            INDEX IDX_FF15E2C0DE12AB56 (created_by),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE preparation_cours ADD CONSTRAINT FK_FF15E2C0BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES professeur (id)');
        $this->addSql('ALTER TABLE preparation_cours ADD CONSTRAINT FK_FF15E2C07ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE preparation_cours ADD CONSTRAINT FK_FF15E2C03338F50D FOREIGN KEY (traitee_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE preparation_cours ADD CONSTRAINT FK_FF15E2C0DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE preparation_cours');
    }
}
