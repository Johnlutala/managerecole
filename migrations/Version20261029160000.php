<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Empêche plusieurs notes pour le même élève et la même évaluation.';
    }

    public function up(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('note');
        if ($table->hasIndex('UNIQ_NOTE_ELEVE_EVALUATION')) {
            return;
        }

        $duplicates = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (SELECT eleve_id, evaluation_id FROM note WHERE evaluation_id IS NOT NULL GROUP BY eleve_id, evaluation_id HAVING COUNT(*) > 1) AS duplicates'
        );
        if ($duplicates > 0) {
            throw new \RuntimeException(sprintf(
                '%d doublon(s) de notes ont été trouvés. Corrigez-les avant d’appliquer cette migration.',
                $duplicates
            ));
        }

        $this->addSql('CREATE UNIQUE INDEX UNIQ_NOTE_ELEVE_EVALUATION ON note (eleve_id, evaluation_id)');
    }

    public function down(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('note');
        if ($table->hasIndex('UNIQ_NOTE_ELEVE_EVALUATION')) {
            $this->addSql('DROP INDEX UNIQ_NOTE_ELEVE_EVALUATION ON note');
        }
    }
}
