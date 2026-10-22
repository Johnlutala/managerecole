<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne la nullabilité des colonnes sur les mappings Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours MODIFY description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE cours MODIFY code VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE cours MODIFY created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE cours MODIFY updated_at DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE section MODIFY code VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE section MODIFY created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE section MODIFY updated_at DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE eleve MODIFY postnom VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE eleve MODIFY phone VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE eleve MODIFY email VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE eleve MODIFY code VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE eleve MODIFY created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE eleve MODIFY updated_at DATETIME DEFAULT NULL');

        $this->addSql("ALTER TABLE messenger_messages MODIFY delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Cette migration aligne les colonnes avec les mappings Doctrine et ne doit pas être annulée.'
        );
    }
}
