<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track grade submission dates and persist messages for teacher inboxes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note ADD date_soumission DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE message_professeur (id INT AUTO_INCREMENT NOT NULL, professeur_id INT NOT NULL, expediteur_id INT DEFAULT NULL, cours_id INT DEFAULT NULL, expediteur_nom VARCHAR(120) NOT NULL, expediteur_email VARCHAR(180) NOT NULL, sujet VARCHAR(180) NOT NULL, contenu LONGTEXT NOT NULL, envoye_le DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', lu_le DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D8757E06BAB22EE9 (professeur_id), INDEX IDX_D8757E0610335F61 (expediteur_id), INDEX IDX_D8757E067ECF78B0 (cours_id), INDEX IDX_MESSAGE_PROFESSEUR_RECIPIENT_READ (professeur_id, lu_le), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message_professeur ADD CONSTRAINT FK_MESSAGE_PROFESSEUR_RECIPIENT FOREIGN KEY (professeur_id) REFERENCES professeur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_professeur ADD CONSTRAINT FK_MESSAGE_PROFESSEUR_EXPEDITEUR FOREIGN KEY (expediteur_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE message_professeur ADD CONSTRAINT FK_MESSAGE_PROFESSEUR_COURS FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE message_professeur');
        $this->addSql('ALTER TABLE note DROP date_soumission');
    }
}
