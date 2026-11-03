<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add annual bulletins calculated from validated student grades.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bulletin_annuel (id INT AUTO_INCREMENT NOT NULL, eleve_id INT NOT NULL, classe_id INT NOT NULL, ecole_id INT NOT NULL, annee_scolaire_id INT NOT NULL, option_id INT DEFAULT NULL, total_cotes INT NOT NULL, total_baremes INT NOT NULL, pourcentage NUMERIC(6, 2) NOT NULL, decision VARCHAR(20) NOT NULL, details JSON NOT NULL, calcule_le DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_BULLETIN_ANNUEL_ELEVE_ANNEE (eleve_id, annee_scolaire_id), INDEX IDX_BULLETIN_ANNUEL_CLASSE_ANNEE (classe_id, annee_scolaire_id), INDEX IDX_BULLETIN_ANNUEL_ECOLE (ecole_id), INDEX IDX_BULLETIN_ANNUEL_OPTION (option_id), INDEX IDX_BULLETIN_ANNUEL_ANNEE (annee_scolaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bulletin_annuel ADD CONSTRAINT FK_BULLETIN_ANNUEL_ELEVE FOREIGN KEY (eleve_id) REFERENCES eleve (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bulletin_annuel ADD CONSTRAINT FK_BULLETIN_ANNUEL_CLASSE FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bulletin_annuel ADD CONSTRAINT FK_BULLETIN_ANNUEL_ECOLE FOREIGN KEY (ecole_id) REFERENCES ecole (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bulletin_annuel ADD CONSTRAINT FK_BULLETIN_ANNUEL_ANNEE FOREIGN KEY (annee_scolaire_id) REFERENCES annee_scolaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bulletin_annuel ADD CONSTRAINT FK_BULLETIN_ANNUEL_OPTION FOREIGN KEY (option_id) REFERENCES `option` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bulletin_annuel');
    }
}
