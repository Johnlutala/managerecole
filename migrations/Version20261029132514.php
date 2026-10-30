<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029132514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('note');
        if ($table->hasColumn('evaluation_id')) {
            return;
        }

        $this->addSql("ALTER TABLE note ADD evaluation_id INT DEFAULT NULL, ADD statut VARCHAR(30) NOT NULL DEFAULT 'brouillon'");
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_CFBDFA14456C5646 ON note (evaluation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14456C5646');
        $this->addSql('DROP INDEX IDX_CFBDFA14456C5646 ON note');
        $this->addSql('ALTER TABLE note DROP evaluation_id, DROP statut');
    }
}
