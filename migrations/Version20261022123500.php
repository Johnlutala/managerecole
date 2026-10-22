<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022123500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associe les élèves à leur option pour filtrer les présences et rattache les inscriptions non ambiguës.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE eleve ADD option_id INT DEFAULT NULL');
        $this->addSql("UPDATE eleve e INNER JOIN (SELECT i.reference, MAX(i.options_id) AS option_id, MAX(i.classe_id) AS classe_id FROM inscription_eleve i INNER JOIN `option` o ON o.id = i.options_id AND o.classe_id = i.classe_id WHERE i.statut = 'validee' AND i.reference IS NOT NULL AND i.options_id IS NOT NULL AND i.classe_id IS NOT NULL GROUP BY i.reference HAVING COUNT(DISTINCT i.options_id) = 1 AND COUNT(DISTINCT i.classe_id) = 1) inscription ON inscription.reference = e.matricule AND inscription.classe_id = e.classe_id SET e.option_id = inscription.option_id");
        $this->addSql('CREATE INDEX IDX_ECA105F7A7C41D6F ON eleve (option_id)');
        $this->addSql('ALTER TABLE eleve ADD CONSTRAINT FK_ECA105F7A7C41D6F FOREIGN KEY (option_id) REFERENCES `option` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE eleve DROP FOREIGN KEY FK_ECA105F7A7C41D6F');
        $this->addSql('DROP INDEX IDX_ECA105F7A7C41D6F ON eleve');
        $this->addSql('ALTER TABLE eleve DROP option_id');
    }
}
