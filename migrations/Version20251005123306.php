<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005123306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE biometric (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, face_data LONGTEXT DEFAULT NULL, right_thumb LONGTEXT DEFAULT NULL, right_index LONGTEXT DEFAULT NULL, right_middle LONGTEXT DEFAULT NULL, right_ring LONGTEXT DEFAULT NULL, right_little LONGTEXT DEFAULT NULL, left_thumb LONGTEXT DEFAULT NULL, left_index LONGTEXT DEFAULT NULL, left_middle LONGTEXT DEFAULT NULL, left_ring LONGTEXT DEFAULT NULL, left_little LONGTEXT DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_10D2B550DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_4FBF094FDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE demographic (id INT AUTO_INCREMENT NOT NULL, biometrics_id INT DEFAULT NULL, created_by INT DEFAULT NULL, firstname VARCHAR(100) NOT NULL, middlename VARCHAR(100) DEFAULT NULL, lastname VARCHAR(100) NOT NULL, gender VARCHAR(10) NOT NULL, phone VARCHAR(25) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F97515D267EF48EC (biometrics_id), INDEX IDX_F97515D2DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE merchant_configuration (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, shortcode VARCHAR(255) NOT NULL, token VARCHAR(500) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_71DDADC54D3E69FD (shortcode), INDEX IDX_71DDADC5DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participant (id INT AUTO_INCREMENT NOT NULL, demographic_id INT DEFAULT NULL, workshop_list_id INT NOT NULL, company_id INT DEFAULT NULL, created_by INT DEFAULT NULL, phone VARCHAR(25) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D79F6B11FF964730 (demographic_id), INDEX IDX_D79F6B117FE1049C (workshop_list_id), INDEX IDX_D79F6B11979B1AD6 (company_id), INDEX IDX_D79F6B11DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE permission (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_E04992AADE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_57698A6A5E237E06 (name), INDEX IDX_57698A6ADE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role_permission (role_id INT NOT NULL, permission_id INT NOT NULL, INDEX IDX_6F7DF886D60322AC (role_id), INDEX IDX_6F7DF886FED90CCA (permission_id), PRIMARY KEY(role_id, permission_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, configuration_id INT DEFAULT NULL, role_id INT DEFAULT NULL, created_by INT DEFAULT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, email VARCHAR(100) NOT NULL, firstname VARCHAR(100) DEFAULT NULL, lastname VARCHAR(100) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D64973F32DD8 (configuration_id), INDEX IDX_8D93D649D60322AC (role_id), INDEX IDX_8D93D649DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, days_number INT DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9B6F02C45E237E06 (name), INDEX IDX_9B6F02C4DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_list (id INT AUTO_INCREMENT NOT NULL, workshop_id INT NOT NULL, created_by INT DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_7E4FD15F1FDCE57C (workshop_id), INDEX IDX_7E4FD15FDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE biometric ADD CONSTRAINT FK_10D2B550DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE company ADD CONSTRAINT FK_4FBF094FDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE demographic ADD CONSTRAINT FK_F97515D267EF48EC FOREIGN KEY (biometrics_id) REFERENCES biometric (id)');
        $this->addSql('ALTER TABLE demographic ADD CONSTRAINT FK_F97515D2DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE merchant_configuration ADD CONSTRAINT FK_71DDADC5DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11FF964730 FOREIGN KEY (demographic_id) REFERENCES demographic (id)');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B117FE1049C FOREIGN KEY (workshop_list_id) REFERENCES workshop_list (id)');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE permission ADD CONSTRAINT FK_E04992AADE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6ADE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64973F32DD8 FOREIGN KEY (configuration_id) REFERENCES merchant_configuration (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE workshop ADD CONSTRAINT FK_9B6F02C4DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE workshop_list ADD CONSTRAINT FK_7E4FD15F1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id)');
        $this->addSql('ALTER TABLE workshop_list ADD CONSTRAINT FK_7E4FD15FDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE biometric DROP FOREIGN KEY FK_10D2B550DE12AB56');
        $this->addSql('ALTER TABLE company DROP FOREIGN KEY FK_4FBF094FDE12AB56');
        $this->addSql('ALTER TABLE demographic DROP FOREIGN KEY FK_F97515D267EF48EC');
        $this->addSql('ALTER TABLE demographic DROP FOREIGN KEY FK_F97515D2DE12AB56');
        $this->addSql('ALTER TABLE merchant_configuration DROP FOREIGN KEY FK_71DDADC5DE12AB56');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11FF964730');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B117FE1049C');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11979B1AD6');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11DE12AB56');
        $this->addSql('ALTER TABLE permission DROP FOREIGN KEY FK_E04992AADE12AB56');
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6ADE12AB56');
        $this->addSql('ALTER TABLE role_permission DROP FOREIGN KEY FK_6F7DF886D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP FOREIGN KEY FK_6F7DF886FED90CCA');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64973F32DD8');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649D60322AC');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649DE12AB56');
        $this->addSql('ALTER TABLE workshop DROP FOREIGN KEY FK_9B6F02C4DE12AB56');
        $this->addSql('ALTER TABLE workshop_list DROP FOREIGN KEY FK_7E4FD15F1FDCE57C');
        $this->addSql('ALTER TABLE workshop_list DROP FOREIGN KEY FK_7E4FD15FDE12AB56');
        $this->addSql('DROP TABLE biometric');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE demographic');
        $this->addSql('DROP TABLE merchant_configuration');
        $this->addSql('DROP TABLE participant');
        $this->addSql('DROP TABLE permission');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE role_permission');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE workshop');
        $this->addSql('DROP TABLE workshop_list');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
