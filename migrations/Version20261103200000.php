<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add school-domain grouping to courses and annual report cards.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours ADD domaine VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours DROP domaine');
    }
}
