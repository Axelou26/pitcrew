<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250309124209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_application CHANGE resume resume VARCHAR(255) NOT NULL, CHANGE cover_letter_file cover_letter_file VARCHAR(255) NOT NULL, CHANGE cover_letter cover_letter_text LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_application CHANGE cover_letter_file cover_letter_file VARCHAR(255) DEFAULT NULL, CHANGE resume resume VARCHAR(255) DEFAULT NULL, CHANGE cover_letter_text cover_letter LONGTEXT NOT NULL');
    }
}
