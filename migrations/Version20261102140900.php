<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261102140900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist the cotation sheet structure selected for each school.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE configuration_fiche_cotation (id INT AUTO_INCREMENT NOT NULL, ecole_id INT NOT NULL, structure VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_9C31A59477EF1B1E (ecole_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE configuration_fiche_cotation ADD CONSTRAINT FK_9C31A59477EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE configuration_fiche_cotation DROP FOREIGN KEY FK_9C31A59477EF1B1E');
        $this->addSql('DROP TABLE configuration_fiche_cotation');
    }
}
