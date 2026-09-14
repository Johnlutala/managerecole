<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914221000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the section relation to classes.';
    }

    public function preUp(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->createSchemaManager()->introspectTable('classe')->hasColumn('section_id'),
            'The section relation already exists on classe.'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classe ADD section_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_8F87BF96D823E37A ON classe (section_id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF96D823E37A FOREIGN KEY (section_id) REFERENCES section (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF96D823E37A');
        $this->addSql('DROP INDEX IDX_8F87BF96D823E37A ON classe');
        $this->addSql('ALTER TABLE classe DROP section_id');
    }
}
