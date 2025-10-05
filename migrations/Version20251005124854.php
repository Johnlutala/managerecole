<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005124854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop ADD description VARCHAR(500) DEFAULT NULL, ADD dates LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', ADD daily_amount DOUBLE PRECISION NOT NULL, ADD currency VARCHAR(5) NOT NULL, DROP days_number');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop ADD days_number INT DEFAULT NULL, DROP description, DROP dates, DROP daily_amount, DROP currency');
    }
}
