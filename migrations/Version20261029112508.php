<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029112508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('evaluation');
        if ($table->hasColumn('types')) {
            $this->addSql('ALTER TABLE evaluation DROP types');
        }
    }

    public function down(Schema $schema): void
    {
        // Aucun retour : la colonne historique « types » n’est plus utilisée.
    }
}
