<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250328215000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE recruiter_user (recruiter_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_71AE4A26156BE243 (recruiter_id), INDEX IDX_71AE4A26A76ED395 (user_id), PRIMARY KEY(recruiter_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_user ADD CONSTRAINT FK_71AE4A26156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_user ADD CONSTRAINT FK_71AE4A26A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer DROP FOREIGN KEY FK_4B4841983481D195
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer DROP FOREIGN KEY FK_4B48419897139001
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC13481D195
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC197139001
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_applicant DROP FOREIGN KEY FK_B2E376EB156BE243
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_applicant DROP FOREIGN KEY FK_B2E376EB97139001
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE applicant_job_offer
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE application
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE recruiter_applicant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP description, DROP technical_skills, DROP soft_skills, DROP cv_filename, DROP education_history, DROP work_experience
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE applicant_job_offer (applicant_id INT NOT NULL, job_offer_id INT NOT NULL, INDEX IDX_4B48419897139001 (applicant_id), INDEX IDX_4B4841983481D195 (job_offer_id), PRIMARY KEY(applicant_id, job_offer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, applicant_id INT NOT NULL, job_offer_id INT NOT NULL, cover_letter LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, cv_filename VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, recommendation_letter_filename VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_A45BDDC197139001 (applicant_id), INDEX IDX_A45BDDC13481D195 (job_offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE recruiter_applicant (recruiter_id INT NOT NULL, applicant_id INT NOT NULL, INDEX IDX_B2E376EB156BE243 (recruiter_id), INDEX IDX_B2E376EB97139001 (applicant_id), PRIMARY KEY(recruiter_id, applicant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer ADD CONSTRAINT FK_4B4841983481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer ADD CONSTRAINT FK_4B48419897139001 FOREIGN KEY (applicant_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE application ADD CONSTRAINT FK_A45BDDC13481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE application ADD CONSTRAINT FK_A45BDDC197139001 FOREIGN KEY (applicant_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_applicant ADD CONSTRAINT FK_B2E376EB156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_applicant ADD CONSTRAINT FK_B2E376EB97139001 FOREIGN KEY (applicant_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_user DROP FOREIGN KEY FK_71AE4A26156BE243
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_user DROP FOREIGN KEY FK_71AE4A26A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE recruiter_user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD description LONGTEXT DEFAULT NULL, ADD technical_skills JSON DEFAULT NULL, ADD soft_skills JSON DEFAULT NULL, ADD cv_filename VARCHAR(255) DEFAULT NULL, ADD education_history LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', ADD work_experience LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'
        SQL);
    }
}
