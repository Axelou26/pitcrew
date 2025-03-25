<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240701000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job_application table';
    }

    public function up(Schema $schema): void
    {
        // Create job_application table if it doesn't exist
        $this->addSql('CREATE TABLE IF NOT EXISTS job_application (
            id INT AUTO_INCREMENT NOT NULL,
            applicant_id INT NOT NULL,
            job_offer_id INT NOT NULL,
            cover_letter LONGTEXT NOT NULL,
            resume VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            status VARCHAR(20) NOT NULL,
            documents JSON NOT NULL,
            INDEX IDX_C737C68897139001 (applicant_id),
            INDEX IDX_C737C6883481D195 (job_offer_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraints
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C68897139001 FOREIGN KEY (applicant_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C6883481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop job_application table
        $this->addSql('DROP TABLE job_application');
    }
} 