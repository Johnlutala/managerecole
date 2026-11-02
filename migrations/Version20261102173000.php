<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add global per-period cotation authorizations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE autorisation_cotation (id INT AUTO_INCREMENT NOT NULL, structure VARCHAR(20) NOT NULL, periode VARCHAR(100) NOT NULL, autorisee TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_AUTORISATION_COTATION_PERIODE (structure, periode), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE autorisation_cotation');
    }
}
