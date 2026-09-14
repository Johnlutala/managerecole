<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Compatibility marker for the migration already executed in production.
 */
final class Version20260904175753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Compatibility marker for the production migration history.';
    }

    public function up(Schema $schema): void {}

    public function down(Schema $schema): void {}
}
