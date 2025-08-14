<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250725095012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message ADD is_read TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user ADD is_active TINYINT(1) DEFAULT NULL, DROP phone, DROP birth_date, DROP experience, CHANGE skills skills JSON NOT NULL, CHANGE documents documents JSON NOT NULL, CHANGE education_history education_history JSON DEFAULT NULL, CHANGE work_experience work_experience JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP is_read');
        $this->addSql('ALTER TABLE `user` ADD phone VARCHAR(20) DEFAULT NULL, ADD birth_date DATE DEFAULT NULL, ADD experience LONGTEXT DEFAULT NULL, DROP is_active, CHANGE skills skills JSON DEFAULT NULL, CHANGE documents documents JSON DEFAULT NULL, CHANGE education_history education_history LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE work_experience work_experience LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }
}
