<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022121500 extends AbstractMigration
{
    public function getDescription(): string { return 'Aligne les index de présence du personnel sur les mappings Doctrine.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE presence RENAME INDEX idx_presence_validated_by TO IDX_6977C7A5C69DE5E5');
        $this->addSql('ALTER TABLE presence_personnel DROP INDEX UNIQ_PRESENCE_PERSONNEL_JOUR');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C04C6C6877153098 ON presence_personnel (code)');
        $this->addSql('ALTER TABLE presence_personnel RENAME INDEX idx_pp_prof TO IDX_C04C6C68BAB22EE9');
        $this->addSql('ALTER TABLE presence_personnel RENAME INDEX idx_pp_user TO IDX_C04C6C681C109075');
        $this->addSql('ALTER TABLE presence_personnel RENAME INDEX idx_pp_created_by TO IDX_C04C6C68DE12AB56');
    }
    public function down(Schema $schema): void { $this->throwIrreversibleMigrationException('Index alignment only.'); }
}
