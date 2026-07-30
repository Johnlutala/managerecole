<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260730132927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE biometric (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, right_thumb LONGTEXT DEFAULT NULL, right_index LONGTEXT DEFAULT NULL, right_middle LONGTEXT DEFAULT NULL, right_ring LONGTEXT DEFAULT NULL, right_little LONGTEXT DEFAULT NULL, left_thumb LONGTEXT DEFAULT NULL, left_index LONGTEXT DEFAULT NULL, left_middle LONGTEXT DEFAULT NULL, left_ring LONGTEXT DEFAULT NULL, left_little LONGTEXT DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_10D2B550DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE merchant_configuration (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, shortcode VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_71DDADC54D3E69FD (shortcode), INDEX IDX_71DDADC5DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participant (id INT AUTO_INCREMENT NOT NULL, biometric_id INT DEFAULT NULL, created_by INT DEFAULT NULL, phone VARCHAR(25) DEFAULT NULL, phone_mobile_money VARCHAR(25) DEFAULT NULL, firstname VARCHAR(50) NOT NULL, middlename VARCHAR(50) DEFAULT NULL, lastname VARCHAR(50) NOT NULL, gender VARCHAR(10) DEFAULT NULL, organization VARCHAR(150) DEFAULT NULL, grade VARCHAR(150) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D79F6B11FB920295 (biometric_id), INDEX IDX_D79F6B11DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, workshop_id INT DEFAULT NULL, day_id INT DEFAULT NULL, participant_id INT DEFAULT NULL, created_by INT DEFAULT NULL, is_present TINYINT(1) NOT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_AB55E24F1FDCE57C (workshop_id), INDEX IDX_AB55E24F9C24126 (day_id), INDEX IDX_AB55E24F9D1C3019 (participant_id), INDEX IDX_AB55E24FDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, configuration_id INT DEFAULT NULL, created_by INT DEFAULT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, email VARCHAR(100) NOT NULL, firstname VARCHAR(100) DEFAULT NULL, lastname VARCHAR(100) DEFAULT NULL, roles JSON NOT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D64973F32DD8 (configuration_id), INDEX IDX_8D93D649DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop (id INT AUTO_INCREMENT NOT NULL, configuration_id INT DEFAULT NULL, created_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, daily_amount DOUBLE PRECISION DEFAULT NULL, currency VARCHAR(5) DEFAULT NULL, is_ended TINYINT(1) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9B6F02C45E237E06 (name), INDEX IDX_9B6F02C473F32DD8 (configuration_id), INDEX IDX_9B6F02C4DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_day (id INT AUTO_INCREMENT NOT NULL, workshop_id INT DEFAULT NULL, created_by INT DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, is_closed TINYINT(1) DEFAULT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D7D8A0F11FDCE57C (workshop_id), INDEX IDX_D7D8A0F1DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE biometric ADD CONSTRAINT FK_10D2B550DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE merchant_configuration ADD CONSTRAINT FK_71DDADC5DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11FB920295 FOREIGN KEY (biometric_id) REFERENCES biometric (id)');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F9C24126 FOREIGN KEY (day_id) REFERENCES workshop_day (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F9D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64973F32DD8 FOREIGN KEY (configuration_id) REFERENCES merchant_configuration (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE workshop ADD CONSTRAINT FK_9B6F02C473F32DD8 FOREIGN KEY (configuration_id) REFERENCES merchant_configuration (id)');
        $this->addSql('ALTER TABLE workshop ADD CONSTRAINT FK_9B6F02C4DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE workshop_day ADD CONSTRAINT FK_D7D8A0F11FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id)');
        $this->addSql('ALTER TABLE workshop_day ADD CONSTRAINT FK_D7D8A0F1DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE biometric DROP FOREIGN KEY FK_10D2B550DE12AB56');
        $this->addSql('ALTER TABLE merchant_configuration DROP FOREIGN KEY FK_71DDADC5DE12AB56');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11FB920295');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11DE12AB56');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F1FDCE57C');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F9C24126');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F9D1C3019');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FDE12AB56');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64973F32DD8');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649DE12AB56');
        $this->addSql('ALTER TABLE workshop DROP FOREIGN KEY FK_9B6F02C473F32DD8');
        $this->addSql('ALTER TABLE workshop DROP FOREIGN KEY FK_9B6F02C4DE12AB56');
        $this->addSql('ALTER TABLE workshop_day DROP FOREIGN KEY FK_D7D8A0F11FDCE57C');
        $this->addSql('ALTER TABLE workshop_day DROP FOREIGN KEY FK_D7D8A0F1DE12AB56');
        $this->addSql('DROP TABLE biometric');
        $this->addSql('DROP TABLE merchant_configuration');
        $this->addSql('DROP TABLE participant');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE workshop');
        $this->addSql('DROP TABLE workshop_day');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
