<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des champs manquants dans la table interview.
 */
final class Version20250419300000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs manquants dans la table interview: status, meeting_id, et relations avec job_offer, applicant et recruiter';
    }

    public function up(Schema $schema): void
    {
        // Vérification si la table existe
        if ($schema->hasTable('interview')) {
            $table = $schema->getTable('interview');

            // Ajouter les colonnes manquantes si elles n'existent pas déjà
            if (!$table->hasColumn('status')) {
                $this->addSql('ALTER TABLE interview ADD status VARCHAR(50) DEFAULT \'scheduled\' NOT NULL');
            }

            if (!$table->hasColumn('meeting_id')) {
                $this->addSql('ALTER TABLE interview ADD meeting_id VARCHAR(255) DEFAULT NULL');
            }

            // Ajouter les relations
            if (!$table->hasColumn('job_offer_id')) {
                $this->addSql('ALTER TABLE interview ADD job_offer_id INT NOT NULL');
                $this->addSql('ALTER TABLE interview ADD CONSTRAINT FK_8DD7F1343481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id)');
                $this->addSql('CREATE INDEX IDX_8DD7F1343481D195 ON interview (job_offer_id)');
            }

            if (!$table->hasColumn('applicant_id')) {
                $this->addSql('ALTER TABLE interview ADD applicant_id INT NOT NULL');
                $this->addSql('ALTER TABLE interview ADD CONSTRAINT FK_8DD7F13497139001 FOREIGN KEY (applicant_id) REFERENCES user (id)');
                $this->addSql('CREATE INDEX IDX_8DD7F13497139001 ON interview (applicant_id)');
            }

            if (!$table->hasColumn('recruiter_id')) {
                $this->addSql('ALTER TABLE interview ADD recruiter_id INT NOT NULL');
                $this->addSql('ALTER TABLE interview ADD CONSTRAINT FK_8DD7F134156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id)');
                $this->addSql('CREATE INDEX IDX_8DD7F134156BE243 ON interview (recruiter_id)');
            }
        } else {
            // Créer la table complète si elle n'existe pas
            $this->addSql('CREATE TABLE interview (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                scheduled_at DATETIME NOT NULL,
                ended_at DATETIME DEFAULT NULL,
                room_id VARCHAR(255) DEFAULT NULL,
                meeting_url VARCHAR(255) DEFAULT NULL,
                notes LONGTEXT DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT \'scheduled\',
                meeting_id VARCHAR(255) DEFAULT NULL,
                job_offer_id INT NOT NULL,
                applicant_id INT NOT NULL,
                recruiter_id INT NOT NULL,
                PRIMARY KEY(id),
                INDEX IDX_8DD7F1343481D195 (job_offer_id),
                INDEX IDX_8DD7F13497139001 (applicant_id),
                INDEX IDX_8DD7F134156BE243 (recruiter_id),
                CONSTRAINT FK_8DD7F1343481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id),
                CONSTRAINT FK_8DD7F13497139001 FOREIGN KEY (applicant_id) REFERENCES user (id),
                CONSTRAINT FK_8DD7F134156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE interview DROP COLUMN status');
        $this->addSql('ALTER TABLE interview DROP COLUMN meeting_id');

        // Si vous souhaitez supprimer les relations également
        $this->addSql('ALTER TABLE interview DROP FOREIGN KEY FK_8DD7F1343481D195');
        $this->addSql('ALTER TABLE interview DROP FOREIGN KEY FK_8DD7F13497139001');
        $this->addSql('ALTER TABLE interview DROP FOREIGN KEY FK_8DD7F134156BE243');
        $this->addSql('ALTER TABLE interview DROP COLUMN job_offer_id');
        $this->addSql('ALTER TABLE interview DROP COLUMN applicant_id');
        $this->addSql('ALTER TABLE interview DROP COLUMN recruiter_id');
    }
}
