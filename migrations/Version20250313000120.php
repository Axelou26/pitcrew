<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313000120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recruiter_subscription (id INT AUTO_INCREMENT NOT NULL, recruiter_id INT NOT NULL, subscription_id INT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, payment_status VARCHAR(50) NOT NULL, remaining_job_offers INT DEFAULT NULL, cancelled TINYINT(1) NOT NULL, auto_renew TINYINT(1) DEFAULT 1 NOT NULL, stripe_subscription_id VARCHAR(255) DEFAULT NULL, INDEX IDX_B74563A3156BE243 (recruiter_id), INDEX IDX_B74563A39A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, price DOUBLE PRECISION NOT NULL, duration INT NOT NULL, features JSON NOT NULL, max_job_offers INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE recruiter_subscription ADD CONSTRAINT FK_B74563A3156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE recruiter_subscription ADD CONSTRAINT FK_B74563A39A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE following DROP FOREIGN KEY FK_71BF8DE3233D34C1');
        $this->addSql('ALTER TABLE following DROP FOREIGN KEY FK_71BF8DE33AD8644E');
        $this->addSql('DROP TABLE following');
        $this->addSql('ALTER TABLE applicant ADD stripe_customer_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE recruiter ADD stripe_customer_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD stripe_customer_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE following (user_source INT NOT NULL, user_target INT NOT NULL, INDEX IDX_71BF8DE33AD8644E (user_source), INDEX IDX_71BF8DE3233D34C1 (user_target), PRIMARY KEY(user_source, user_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE following ADD CONSTRAINT FK_71BF8DE3233D34C1 FOREIGN KEY (user_target) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE following ADD CONSTRAINT FK_71BF8DE33AD8644E FOREIGN KEY (user_source) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recruiter_subscription DROP FOREIGN KEY FK_B74563A3156BE243');
        $this->addSql('ALTER TABLE recruiter_subscription DROP FOREIGN KEY FK_B74563A39A1887DC');
        $this->addSql('DROP TABLE recruiter_subscription');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('ALTER TABLE applicant DROP stripe_customer_id');
        $this->addSql('ALTER TABLE recruiter DROP stripe_customer_id');
        $this->addSql('ALTER TABLE user DROP stripe_customer_id');
    }
}
