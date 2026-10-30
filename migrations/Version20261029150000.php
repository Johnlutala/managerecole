<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lier les notes aux évaluations et suivre leur soumission par les professeurs.';
    }

    public function up(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('note');
        if ($table->hasColumn('evaluation_id')) {
            return;
        }

        $this->addSql("ALTER TABLE note ADD evaluation_id INT DEFAULT NULL, ADD statut VARCHAR(30) NOT NULL DEFAULT 'brouillon'");
        $this->addSql('CREATE INDEX IDX_NOTE_EVALUATION ON note (evaluation_id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_NOTE_EVALUATION FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_NOTE_EVALUATION');
        $this->addSql('DROP INDEX IDX_NOTE_EVALUATION ON note');
        $this->addSql('ALTER TABLE note DROP evaluation_id, DROP statut');
    }
}
