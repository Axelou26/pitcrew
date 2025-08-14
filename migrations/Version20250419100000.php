<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Création de la table applicant_job_offer pour les favoris.
 */
final class Version20250419100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table applicant_job_offer pour les favoris';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE applicant_job_offer (
                applicant_id INT NOT NULL,
                job_offer_id INT NOT NULL,
                INDEX IDX_4B48419897139001 (applicant_id),
                INDEX IDX_4B4841983481D195 (job_offer_id),
                PRIMARY KEY(applicant_id, job_offer_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer
            ADD CONSTRAINT FK_4B48419897139001 FOREIGN KEY (applicant_id) REFERENCES user (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_4B4841983481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer DROP FOREIGN KEY FK_4B48419897139001
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer DROP FOREIGN KEY FK_4B4841983481D195
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE applicant_job_offer
        SQL);
    }
}
