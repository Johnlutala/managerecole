<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link user accounts to students.';
    }

    public function preUp(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->createSchemaManager()->introspectTable('user')->hasColumn('eleve_id'),
            'The student relation already exists on user.'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD eleve_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649A6CC7B2 ON `user` (eleve_id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649A6CC7B2 FOREIGN KEY (eleve_id) REFERENCES eleve (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649A6CC7B2');
        $this->addSql('DROP INDEX UNIQ_8D93D649A6CC7B2 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP eleve_id');
    }
}
