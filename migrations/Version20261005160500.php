<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005160500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email and user accounts to parents and link user accounts to professors.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('parents')->hasColumn('email')) {
            $this->addSql('ALTER TABLE parents ADD email VARCHAR(100) DEFAULT NULL');
        }
        if (!$schema->getTable('parents')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE parents ADD user_id INT DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_PARENTS_USER ON parents (user_id)');
            $this->addSql('ALTER TABLE parents ADD CONSTRAINT FK_PARENTS_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        }
        if (!$schema->getTable('professeur')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE professeur ADD user_id INT DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_PROFESSEUR_USER ON professeur (user_id)');
            $this->addSql('ALTER TABLE professeur ADD CONSTRAINT FK_PROFESSEUR_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('professeur')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE professeur DROP FOREIGN KEY FK_PROFESSEUR_USER');
            $this->addSql('DROP INDEX UNIQ_PROFESSEUR_USER ON professeur');
            $this->addSql('ALTER TABLE professeur DROP user_id');
        }
        if ($schema->getTable('parents')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE parents DROP FOREIGN KEY FK_PARENTS_USER');
            $this->addSql('DROP INDEX UNIQ_PARENTS_USER ON parents');
            $this->addSql('ALTER TABLE parents DROP user_id');
        }
        if ($schema->getTable('parents')->hasColumn('email')) {
            $this->addSql('ALTER TABLE parents DROP email');
        }
    }
}
