<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260914182214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE annee_scolaire (id INT AUTO_INCREMENT NOT NULL, ecole_id INT DEFAULT NULL, libelle VARCHAR(50) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, INDEX IDX_97150C2B77EF1B1E (ecole_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, ecole_id INT DEFAULT NULL, section_id INT DEFAULT NULL, created_by INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, capacite INT DEFAULT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8F87BF9677153098 (code), INDEX IDX_8F87BF9677EF1B1E (ecole_id), INDEX IDX_8F87BF96D823E37A (section_id), INDEX IDX_8F87BF96DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cours (id INT AUTO_INCREMENT NOT NULL, classe_id INT DEFAULT NULL, professeur_id INT DEFAULT NULL, created_by INT DEFAULT NULL, nom VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_FDCA8C9C77153098 (code), INDEX IDX_FDCA8C9C8F5EA509 (classe_id), INDEX IDX_FDCA8C9CBAB22EE9 (professeur_id), INDEX IDX_FDCA8C9CDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ecole (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, phone VARCHAR(30) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9786AAC77153098 (code), INDEX IDX_9786AACDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eleve (id INT AUTO_INCREMENT NOT NULL, ecole_id INT DEFAULT NULL, classe_id INT DEFAULT NULL, created_by INT DEFAULT NULL, matricule VARCHAR(30) NOT NULL, nom VARCHAR(50) NOT NULL, postnom VARCHAR(50) DEFAULT NULL, prenom VARCHAR(50) NOT NULL, sexe VARCHAR(20) NOT NULL, date_naissance DATE NOT NULL, lieu_naissance VARCHAR(100) NOT NULL, adresse VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(50) DEFAULT NULL, date_inscription DATE NOT NULL, statut TINYINT(1) NOT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_ECA105F712B2DC9C (matricule), UNIQUE INDEX UNIQ_ECA105F777153098 (code), INDEX IDX_ECA105F777EF1B1E (ecole_id), INDEX IDX_ECA105F78F5EA509 (classe_id), INDEX IDX_ECA105F7DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inscription_eleve (id INT AUTO_INCREMENT NOT NULL, etablissement_id INT DEFAULT NULL, annee_scolaire_id INT DEFAULT NULL, section_id INT DEFAULT NULL, classe_id INT DEFAULT NULL, options_id INT DEFAULT NULL, created_by INT DEFAULT NULL, nom VARCHAR(50) NOT NULL, postnom VARCHAR(50) NOT NULL, prenom VARCHAR(50) DEFAULT NULL, sexe VARCHAR(20) NOT NULL, date_naissance DATE NOT NULL, lieu_naissance VARCHAR(50) NOT NULL, nationalite VARCHAR(50) DEFAULT NULL, adresse VARCHAR(255) NOT NULL, telephone VARCHAR(50) DEFAULT NULL, reference VARCHAR(50) DEFAULT NULL, type_inscription VARCHAR(50) DEFAULT NULL, statut VARCHAR(50) DEFAULT NULL, nom_parent VARCHAR(50) NOT NULL, postnom_parent VARCHAR(50) DEFAULT NULL, prenom_parent VARCHAR(50) DEFAULT NULL, telephone_parent VARCHAR(50) DEFAULT NULL, email_parent VARCHAR(50) DEFAULT NULL, adresse_parent VARCHAR(255) DEFAULT NULL, profession_parent VARCHAR(255) DEFAULT NULL, lien_parental VARCHAR(50) NOT NULL, date_validation DATETIME DEFAULT NULL, motif_rejet VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_BEE851BAFF631228 (etablissement_id), INDEX IDX_BEE851BA9331C741 (annee_scolaire_id), INDEX IDX_BEE851BAD823E37A (section_id), INDEX IDX_BEE851BA8F5EA509 (classe_id), INDEX IDX_BEE851BA3ADB05F1 (options_id), INDEX IDX_BEE851BADE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, eleve_id INT DEFAULT NULL, cote NUMERIC(10, 0) NOT NULL, observation LONGTEXT DEFAULT NULL, INDEX IDX_CFBDFA14A6CC7B2 (eleve_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `option` (id INT AUTO_INCREMENT NOT NULL, classe_id INT DEFAULT NULL, created_by INT DEFAULT NULL, nom VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5A8600B077153098 (code), INDEX IDX_5A8600B08F5EA509 (classe_id), INDEX IDX_5A8600B0DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parents (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, nom VARCHAR(50) NOT NULL, postnom VARCHAR(50) DEFAULT NULL, prenom VARCHAR(50) DEFAULT NULL, telephone VARCHAR(50) NOT NULL, adresse VARCHAR(255) NOT NULL, profession VARCHAR(100) DEFAULT NULL, type VARCHAR(50) NOT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_FD501D6A77153098 (code), INDEX IDX_FD501D6ADE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parents_eleve (parents_id INT NOT NULL, eleve_id INT NOT NULL, INDEX IDX_21977945B706B6D3 (parents_id), INDEX IDX_21977945A6CC7B2 (eleve_id), PRIMARY KEY(parents_id, eleve_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presence (id INT AUTO_INCREMENT NOT NULL, eleve_id INT DEFAULT NULL, classe_id INT DEFAULT NULL, created_by INT DEFAULT NULL, date DATE NOT NULL, statut VARCHAR(50) NOT NULL, heure_arrivee TIME DEFAULT NULL, heure_depart TIME DEFAULT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_6977C7A577153098 (code), INDEX IDX_6977C7A5A6CC7B2 (eleve_id), INDEX IDX_6977C7A58F5EA509 (classe_id), INDEX IDX_6977C7A5DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE professeur (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, matricule VARCHAR(30) NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, postnom VARCHAR(50) DEFAULT NULL, sexe VARCHAR(15) NOT NULL, date_naissance DATE DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(50) DEFAULT NULL, adresse VARCHAR(100) NOT NULL, specialite VARCHAR(150) DEFAULT NULL, date_embauche DATETIME NOT NULL, statut VARCHAR(255) DEFAULT NULL, grade VARCHAR(50) NOT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_17A5529912B2DC9C (matricule), UNIQUE INDEX UNIQ_17A5529977153098 (code), INDEX IDX_17A55299DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE professeur_ecole (professeur_id INT NOT NULL, ecole_id INT NOT NULL, INDEX IDX_982601D2BAB22EE9 (professeur_id), INDEX IDX_982601D277EF1B1E (ecole_id), PRIMARY KEY(professeur_id, ecole_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE section (id INT AUTO_INCREMENT NOT NULL, ecole_id INT DEFAULT NULL, created_by INT DEFAULT NULL, nom VARCHAR(100) NOT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_2D737AEF77153098 (code), INDEX IDX_2D737AEF77EF1B1E (ecole_id), INDEX IDX_2D737AEFDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, email VARCHAR(100) NOT NULL, firstname VARCHAR(100) DEFAULT NULL, lastname VARCHAR(100) DEFAULT NULL, roles JSON NOT NULL, code VARCHAR(50) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D64977153098 (code), INDEX IDX_8D93D649DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE annee_scolaire ADD CONSTRAINT FK_97150C2B77EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF9677EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF96D823E37A FOREIGN KEY (section_id) REFERENCES section (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF96DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9C8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CBAB22EE9 FOREIGN KEY (professeur_id) REFERENCES professeur (id)');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9CDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ecole ADD CONSTRAINT FK_9786AACDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE eleve ADD CONSTRAINT FK_ECA105F777EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id)');
        $this->addSql('ALTER TABLE eleve ADD CONSTRAINT FK_ECA105F78F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE eleve ADD CONSTRAINT FK_ECA105F7DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BAFF631228 FOREIGN KEY (etablissement_id) REFERENCES ecole (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BA9331C741 FOREIGN KEY (annee_scolaire_id) REFERENCES annee_scolaire (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BAD823E37A FOREIGN KEY (section_id) REFERENCES section (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BA8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BA3ADB05F1 FOREIGN KEY (options_id) REFERENCES `option` (id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BADE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14A6CC7B2 FOREIGN KEY (eleve_id) REFERENCES eleve (id)');
        $this->addSql('ALTER TABLE `option` ADD CONSTRAINT FK_5A8600B08F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE `option` ADD CONSTRAINT FK_5A8600B0DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE parents ADD CONSTRAINT FK_FD501D6ADE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE parents_eleve ADD CONSTRAINT FK_21977945B706B6D3 FOREIGN KEY (parents_id) REFERENCES parents (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parents_eleve ADD CONSTRAINT FK_21977945A6CC7B2 FOREIGN KEY (eleve_id) REFERENCES eleve (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A5A6CC7B2 FOREIGN KEY (eleve_id) REFERENCES eleve (id)');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A58F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A5DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE professeur ADD CONSTRAINT FK_17A55299DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE professeur_ecole ADD CONSTRAINT FK_982601D2BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES professeur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE professeur_ecole ADD CONSTRAINT FK_982601D277EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF77EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id)');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEFDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annee_scolaire DROP FOREIGN KEY FK_97150C2B77EF1B1E');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF9677EF1B1E');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF96D823E37A');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF96DE12AB56');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9C8F5EA509');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CBAB22EE9');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9CDE12AB56');
        $this->addSql('ALTER TABLE ecole DROP FOREIGN KEY FK_9786AACDE12AB56');
        $this->addSql('ALTER TABLE eleve DROP FOREIGN KEY FK_ECA105F777EF1B1E');
        $this->addSql('ALTER TABLE eleve DROP FOREIGN KEY FK_ECA105F78F5EA509');
        $this->addSql('ALTER TABLE eleve DROP FOREIGN KEY FK_ECA105F7DE12AB56');
        $this->addSql('ALTER TABLE inscription_eleve DROP FOREIGN KEY FK_BEE851BAFF631228');
        $this->addSql('ALTER TABLE inscription_eleve DROP FOREIGN KEY FK_BEE851BA9331C741');
        $this->addSql('ALTER TABLE inscription_eleve DROP FOREIGN KEY FK_BEE851BAD823E37A');
        $this->addSql('ALTER TABLE inscription_eleve DROP FOREIGN KEY FK_BEE851BA8F5EA509');
        $this->addSql('ALTER TABLE inscription_eleve DROP FOREIGN KEY FK_BEE851BA3ADB05F1');
        $this->addSql('ALTER TABLE inscription_eleve DROP FOREIGN KEY FK_BEE851BADE12AB56');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14A6CC7B2');
        $this->addSql('ALTER TABLE `option` DROP FOREIGN KEY FK_5A8600B08F5EA509');
        $this->addSql('ALTER TABLE `option` DROP FOREIGN KEY FK_5A8600B0DE12AB56');
        $this->addSql('ALTER TABLE parents DROP FOREIGN KEY FK_FD501D6ADE12AB56');
        $this->addSql('ALTER TABLE parents_eleve DROP FOREIGN KEY FK_21977945B706B6D3');
        $this->addSql('ALTER TABLE parents_eleve DROP FOREIGN KEY FK_21977945A6CC7B2');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A5A6CC7B2');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A58F5EA509');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A5DE12AB56');
        $this->addSql('ALTER TABLE professeur DROP FOREIGN KEY FK_17A55299DE12AB56');
        $this->addSql('ALTER TABLE professeur_ecole DROP FOREIGN KEY FK_982601D2BAB22EE9');
        $this->addSql('ALTER TABLE professeur_ecole DROP FOREIGN KEY FK_982601D277EF1B1E');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF77EF1B1E');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEFDE12AB56');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649DE12AB56');
        $this->addSql('DROP TABLE annee_scolaire');
        $this->addSql('DROP TABLE classe');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE ecole');
        $this->addSql('DROP TABLE eleve');
        $this->addSql('DROP TABLE inscription_eleve');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE `option`');
        $this->addSql('DROP TABLE parents');
        $this->addSql('DROP TABLE parents_eleve');
        $this->addSql('DROP TABLE presence');
        $this->addSql('DROP TABLE professeur');
        $this->addSql('DROP TABLE professeur_ecole');
        $this->addSql('DROP TABLE section');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
