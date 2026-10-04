<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923134325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void {}

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE professeur_ecole (professeur_id INT NOT NULL, ecole_id INT NOT NULL, INDEX IDX_982601D277EF1B1E (ecole_id), INDEX IDX_982601D2BAB22EE9 (professeur_id), PRIMARY KEY(professeur_id, ecole_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE professeur_ecole ADD CONSTRAINT FK_982601D277EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE professeur_ecole ADD CONSTRAINT FK_982601D2BAB22EE9 FOREIGN KEY (professeur_id) REFERENCES professeur (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_BEE851BAE7927C74 ON inscription_eleve');
        $this->addSql('ALTER TABLE professeur DROP FOREIGN KEY FK_17A5529977EF1B1E');
        $this->addSql('DROP INDEX IDX_17A5529977EF1B1E ON professeur');
        $this->addSql('ALTER TABLE professeur DROP ecole_id');
    }
}
