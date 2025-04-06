<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240701000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional_skills column to job_offer table';
    }

    public function up(Schema $schema): void
    {
        // Add the optional_skills column as JSON
        $this->addSql('ALTER TABLE job_offer ADD optional_skills JSON DEFAULT NULL');
        
        // Set default empty array for all existing records
        $this->addSql('UPDATE job_offer SET optional_skills = \'[]\'');
        
        // Add a new column for the application deadline
        $this->addSql('ALTER TABLE job_offer ADD application_deadline DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Drop the optional_skills column
        $this->addSql('ALTER TABLE job_offer DROP optional_skills');
        
        // Drop the application_deadline column
        $this->addSql('ALTER TABLE job_offer DROP application_deadline');
    }
} 