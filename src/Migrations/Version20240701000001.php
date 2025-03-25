<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240701000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job_offer table';
    }

    public function up(Schema $schema): void
    {
        // Create job_offer table if it doesn't exist
        $this->addSql('CREATE TABLE IF NOT EXISTS job_offer (
            id INT AUTO_INCREMENT NOT NULL,
            recruiter_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            contract_type VARCHAR(50) NOT NULL,
            location VARCHAR(255) NOT NULL,
            salary INT DEFAULT NULL,
            required_skills JSON NOT NULL,
            expires_at DATE DEFAULT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_288A3A4E156BE243 (recruiter_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE job_offer ADD CONSTRAINT FK_288A3A4E156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop job_offer table
        $this->addSql('DROP TABLE job_offer');
    }
} 