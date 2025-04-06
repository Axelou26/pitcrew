<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240701000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix required_skills column type in job_offer table';
    }

    public function up(Schema $schema): void
    {
        // Update the required_skills column to JSON type
        $this->addSql('ALTER TABLE job_offer CHANGE required_skills required_skills JSON NOT NULL');
        
        // Set default empty array for all existing records
        $this->addSql('UPDATE job_offer SET required_skills = \'[]\'');
    }

    public function down(Schema $schema): void
    {
        // Revert back to ARRAY type
        $this->addSql('ALTER TABLE job_offer CHANGE required_skills required_skills LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
    }
} 