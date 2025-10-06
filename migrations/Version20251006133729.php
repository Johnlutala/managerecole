<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006133729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B117FE1049C');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, workshop_id INT DEFAULT NULL, day_id INT DEFAULT NULL, participant_id INT DEFAULT NULL, created_by INT DEFAULT NULL, is_present TINYINT(1) NOT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_AB55E24F1FDCE57C (workshop_id), INDEX IDX_AB55E24F9C24126 (day_id), INDEX IDX_AB55E24F9D1C3019 (participant_id), INDEX IDX_AB55E24FDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_day (id INT AUTO_INCREMENT NOT NULL, workshop_id INT DEFAULT NULL, created_by INT DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, code VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D7D8A0F11FDCE57C (workshop_id), INDEX IDX_D7D8A0F1DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F9C24126 FOREIGN KEY (day_id) REFERENCES workshop_day (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F9D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE workshop_day ADD CONSTRAINT FK_D7D8A0F11FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id)');
        $this->addSql('ALTER TABLE workshop_day ADD CONSTRAINT FK_D7D8A0F1DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE workshop_list DROP FOREIGN KEY FK_7E4FD15FDE12AB56');
        $this->addSql('ALTER TABLE workshop_list DROP FOREIGN KEY FK_7E4FD15F1FDCE57C');
        $this->addSql('DROP TABLE workshop_list');
        $this->addSql('DROP INDEX IDX_D79F6B117FE1049C ON participant');
        $this->addSql('ALTER TABLE participant DROP workshop_list_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workshop_list (id INT AUTO_INCREMENT NOT NULL, workshop_id INT NOT NULL, created_by INT DEFAULT NULL, code VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, enabled TINYINT(1) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_7E4FD15F1FDCE57C (workshop_id), INDEX IDX_7E4FD15FDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE workshop_list ADD CONSTRAINT FK_7E4FD15FDE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_list ADD CONSTRAINT FK_7E4FD15F1FDCE57C FOREIGN KEY (workshop_id) REFERENCES workshop (id)');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F1FDCE57C');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F9C24126');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F9D1C3019');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FDE12AB56');
        $this->addSql('ALTER TABLE workshop_day DROP FOREIGN KEY FK_D7D8A0F11FDCE57C');
        $this->addSql('ALTER TABLE workshop_day DROP FOREIGN KEY FK_D7D8A0F1DE12AB56');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE workshop_day');
        $this->addSql('ALTER TABLE participant ADD workshop_list_id INT NOT NULL');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B117FE1049C FOREIGN KEY (workshop_list_id) REFERENCES workshop_list (id)');
        $this->addSql('CREATE INDEX IDX_D79F6B117FE1049C ON participant (workshop_list_id)');
    }
}
