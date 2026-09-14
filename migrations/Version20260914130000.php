<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914130000 extends AbstractMigration
{
    public function getDescription(): string { return 'Allow a registration to remain pending until validation.'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE inscription_eleve MODIFY date_validation DATETIME DEFAULT NULL'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE inscription_eleve MODIFY date_validation DATETIME NOT NULL'); }
}
