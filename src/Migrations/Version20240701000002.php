<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240701000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add logo and image fields to job_offer table';
    }

    /**
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public function up(Schema $schema): void
    {
        // Add logo_url column
        $this->addSql('ALTER TABLE job_offer ADD logo_url VARCHAR(255) DEFAULT NULL');

        // Add image column
        $this->addSql('ALTER TABLE job_offer ADD image VARCHAR(255) DEFAULT NULL');

        // Add company_name column
        $this->addSql('ALTER TABLE job_offer ADD company_name VARCHAR(255) DEFAULT NULL');

        // Add company_description column
        $this->addSql('ALTER TABLE job_offer ADD company_description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Drop the added columns
        $this->addSql('ALTER TABLE job_offer DROP logo_url');
        $this->addSql('ALTER TABLE job_offer DROP image');
        $this->addSql('ALTER TABLE job_offer DROP company_name');
        $this->addSql('ALTER TABLE job_offer DROP company_description');
    }
}
