<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250307192743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_application (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, job_offer_id INT NOT NULL, cover_letter LONGTEXT NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, documents JSON NOT NULL, INDEX IDX_C737C688A76ED395 (user_id), INDEX IDX_C737C6883481D195 (job_offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C688A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C6883481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id)');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C7294869C');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE category');
        $this->addSql('ALTER TABLE applicant ADD skills JSON DEFAULT NULL, ADD cv VARCHAR(255) DEFAULT NULL, ADD documents JSON DEFAULT NULL, ADD job_title VARCHAR(255) DEFAULT NULL, ADD experience LONGTEXT DEFAULT NULL, ADD education LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offer CHANGE contract_type contract_type VARCHAR(255) NOT NULL, CHANGE salary salary DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE recruiter ADD skills JSON DEFAULT NULL, ADD cv VARCHAR(255) DEFAULT NULL, ADD documents JSON DEFAULT NULL, ADD job_title VARCHAR(255) DEFAULT NULL, ADD experience LONGTEXT DEFAULT NULL, ADD education LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD skills JSON DEFAULT NULL, ADD cv VARCHAR(255) DEFAULT NULL, ADD documents JSON DEFAULT NULL, ADD job_title VARCHAR(255) DEFAULT NULL, ADD experience LONGTEXT DEFAULT NULL, ADD education LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, photo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, publishe_at DATETIME NOT NULL, author_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, body VARCHAR(10000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, content VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, author VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX IDX_9474526C7294869C (article_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C688A76ED395');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C6883481D195');
        $this->addSql('DROP TABLE job_application');
        $this->addSql('ALTER TABLE user DROP skills, DROP cv, DROP documents, DROP job_title, DROP experience, DROP education');
        $this->addSql('ALTER TABLE recruiter DROP skills, DROP cv, DROP documents, DROP job_title, DROP experience, DROP education');
        $this->addSql('ALTER TABLE applicant DROP skills, DROP cv, DROP documents, DROP job_title, DROP experience, DROP education');
        $this->addSql('ALTER TABLE job_offer CHANGE contract_type contract_type VARCHAR(50) NOT NULL, CHANGE salary salary INT DEFAULT NULL');
    }
}
