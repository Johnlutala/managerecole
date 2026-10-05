<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005105000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow a course to be linked to an optional class option.';
    }

    public function preUp(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->createSchemaManager()->introspectTable('cours')->hasColumn('option_id'),
            'The option relation already exists on cours.'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours ADD option_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_COURS_OPTION ON cours (option_id)');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_COURS_OPTION FOREIGN KEY (option_id) REFERENCES `option` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_COURS_OPTION');
        $this->addSql('DROP INDEX IDX_COURS_OPTION ON cours');
        $this->addSql('ALTER TABLE cours DROP option_id');
    }
}
