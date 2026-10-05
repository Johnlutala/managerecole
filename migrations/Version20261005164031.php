<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005164031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les relations école, année scolaire et section aux professeurs';
    }

    public function up(Schema $schema): void
{
    $table = $this->connection
        ->createSchemaManager()
        ->introspectTable('professeur');

    if (!$table->hasColumn('ecole_id')) {
        $this->addSql('ALTER TABLE professeur ADD ecole_id INT DEFAULT NULL');
    }

    if (!$table->hasColumn('annee_scolaire_id')) {
        $this->addSql('ALTER TABLE professeur ADD annee_scolaire_id INT DEFAULT NULL');
    }

    if (!$table->hasColumn('section_id')) {
        $this->addSql('ALTER TABLE professeur ADD section_id INT DEFAULT NULL');
    }
}
    public function down(Schema $schema): void
{
    $table = $this->connection
        ->createSchemaManager()
        ->introspectTable('professeur');

    if ($table->hasColumn('ecole_id')) {
        $this->addSql('ALTER TABLE professeur DROP ecole_id');
    }

    if ($table->hasColumn('annee_scolaire_id')) {
        $this->addSql('ALTER TABLE professeur DROP annee_scolaire_id');
    }

    if ($table->hasColumn('section_id')) {
        $this->addSql('ALTER TABLE professeur DROP section_id');
    }
}
}