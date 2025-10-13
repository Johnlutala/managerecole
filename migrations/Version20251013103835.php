<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013103835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop ADD configuration_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop ADD CONSTRAINT FK_9B6F02C473F32DD8 FOREIGN KEY (configuration_id) REFERENCES merchant_configuration (id)');
        $this->addSql('CREATE INDEX IDX_9B6F02C473F32DD8 ON workshop (configuration_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop DROP FOREIGN KEY FK_9B6F02C473F32DD8');
        $this->addSql('DROP INDEX IDX_9B6F02C473F32DD8 ON workshop');
        $this->addSql('ALTER TABLE workshop DROP configuration_id');
    }
}
