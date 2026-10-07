<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne la nullabilité des colonnes de production sur les mappings Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `option` CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE classe CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE creneau_horaire CHANGE statut statut VARCHAR(50) DEFAULT NULL, CHANGE heure_debut_reelle heure_debut_reelle TIME DEFAULT NULL, CHANGE heure_fin_reelle heure_fin_reelle TIME DEFAULT NULL');
        $this->addSql("UPDATE `user` SET roles = JSON_ARRAY() WHERE roles IS NULL");
        $this->addSql('ALTER TABLE `user` CHANGE username username VARCHAR(255) DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE firstname firstname VARCHAR(100) DEFAULT NULL, CHANGE lastname lastname VARCHAR(100) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ecole CHANGE phone phone VARCHAR(30) DEFAULT NULL, CHANGE email email VARCHAR(180) DEFAULT NULL, CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription_eleve CHANGE prenom prenom VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(50) DEFAULT NULL, CHANGE telephone telephone VARCHAR(50) DEFAULT NULL, CHANGE reference reference VARCHAR(50) DEFAULT NULL, CHANGE type_inscription type_inscription VARCHAR(50) DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL, CHANGE postnom_parent postnom_parent VARCHAR(50) DEFAULT NULL, CHANGE prenom_parent prenom_parent VARCHAR(50) DEFAULT NULL, CHANGE telephone_parent telephone_parent VARCHAR(50) DEFAULT NULL, CHANGE email_parent email_parent VARCHAR(50) DEFAULT NULL, CHANGE adresse_parent adresse_parent VARCHAR(255) DEFAULT NULL, CHANGE profession_parent profession_parent VARCHAR(255) DEFAULT NULL, CHANGE date_validation date_validation DATETIME DEFAULT NULL, CHANGE motif_rejet motif_rejet VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE presence CHANGE heure_arrivee heure_arrivee TIME DEFAULT NULL, CHANGE heure_depart heure_depart TIME DEFAULT NULL, CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE professeur CHANGE postnom postnom VARCHAR(50) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(50) DEFAULT NULL, CHANGE specialite specialite VARCHAR(150) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE parents CHANGE postnom postnom VARCHAR(50) DEFAULT NULL, CHANGE prenom prenom VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(100) DEFAULT NULL, CHANGE profession profession VARCHAR(100) DEFAULT NULL, CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE cours CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE section CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE eleve CHANGE postnom postnom VARCHAR(50) DEFAULT NULL, CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(50) DEFAULT NULL, CHANGE code code VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql("ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Cette migration aligne le schéma sans perte de données et ne doit pas être annulée.'
        );
    }
}
