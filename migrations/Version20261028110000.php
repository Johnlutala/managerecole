<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261028110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permet de rattacher une demande d’inscription à un parent déjà enregistré.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription_eleve ADD parent_existant_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_BEE851BA96793704 ON inscription_eleve (parent_existant_id)');
        $this->addSql('ALTER TABLE inscription_eleve ADD CONSTRAINT FK_BEE851BA96793704 FOREIGN KEY (parent_existant_id) REFERENCES parents (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription_eleve DROP FOREIGN KEY FK_BEE851BA96793704');
        $this->addSql('DROP INDEX IDX_BEE851BA96793704 ON inscription_eleve');
        $this->addSql('ALTER TABLE inscription_eleve DROP parent_existant_id');
    }
}
