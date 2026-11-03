<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create persisted student bulletins after administrative grade validation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bulletin (id INT AUTO_INCREMENT NOT NULL, eleve_id INT NOT NULL, configuration_bulletin_id INT NOT NULL, total_cotes INT NOT NULL, total_baremes INT NOT NULL, pourcentage NUMERIC(5, 2) NOT NULL, decision VARCHAR(20) NOT NULL, details JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_BULLETIN_ELEVE_CONFIGURATION (eleve_id, configuration_bulletin_id), INDEX IDX_BULLETIN_ELEVE (eleve_id), INDEX IDX_BULLETIN_CONFIGURATION (configuration_bulletin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_BULLETIN_ELEVE FOREIGN KEY (eleve_id) REFERENCES eleve (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_BULLETIN_CONFIGURATION FOREIGN KEY (configuration_bulletin_id) REFERENCES configuration_bulletin (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bulletin');
    }
}
