<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410164852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, applicant_id INT NOT NULL, job_offer_id INT NOT NULL, cover_letter LONGTEXT DEFAULT NULL, cv_filename VARCHAR(255) DEFAULT NULL, rec_letter_filename VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(20) NOT NULL, INDEX IDX_A45BDDC197139001 (applicant_id), INDEX IDX_A45BDDC13481D195 (job_offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, participant1_id INT NOT NULL, participant2_id INT NOT NULL, job_application_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', last_message_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_8A8E26E9B29A9963 (participant1_id), INDEX IDX_8A8E26E9A02F368D (participant2_id), INDEX IDX_8A8E26E9AC7A5A08 (job_application_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE education (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, degree VARCHAR(255) NOT NULL, institution VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, start_date VARCHAR(10) NOT NULL, end_date VARCHAR(10) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_DB0A5ED2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE favorite (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, job_offer_id INT DEFAULT NULL, candidate_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_68C58ED9A76ED395 (user_id), INDEX IDX_68C58ED93481D195 (job_offer_id), INDEX IDX_68C58ED991BD8781 (candidate_id), UNIQUE INDEX unique_favorite (user_id, job_offer_id, candidate_id, type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE friendship (id INT AUTO_INCREMENT NOT NULL, requester_id INT NOT NULL, addressee_id INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7234A45FED442CF4 (requester_id), INDEX IDX_7234A45F2261B4C3 (addressee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE hashtag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, usage_count INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', last_used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_5AB52A615E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE interview (id INT AUTO_INCREMENT NOT NULL, recruiter_id INT NOT NULL, applicant_id INT NOT NULL, job_offer_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, scheduled_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, room_id VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, meeting_url VARCHAR(255) DEFAULT NULL, INDEX IDX_CF1D3C34156BE243 (recruiter_id), INDEX IDX_CF1D3C3497139001 (applicant_id), INDEX IDX_CF1D3C343481D195 (job_offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE job_application (id INT AUTO_INCREMENT NOT NULL, applicant_id INT NOT NULL, job_offer_id INT NOT NULL, cover_letter LONGTEXT NOT NULL, resume VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(20) NOT NULL, documents JSON NOT NULL, resume_s3_key VARCHAR(255) DEFAULT NULL, resume_url VARCHAR(255) DEFAULT NULL, documents_s3_keys JSON DEFAULT NULL, documents_urls JSON DEFAULT NULL, INDEX IDX_C737C68897139001 (applicant_id), INDEX IDX_C737C6883481D195 (job_offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE job_offer (id INT AUTO_INCREMENT NOT NULL, recruiter_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, location VARCHAR(255) NOT NULL, contract_type VARCHAR(50) NOT NULL, salary INT DEFAULT NULL, required_skills JSON NOT NULL, expires_at DATE DEFAULT NULL, is_remote TINYINT(1) NOT NULL, is_promoted TINYINT(1) NOT NULL, experience_level VARCHAR(50) NOT NULL, company VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, is_published TINYINT(1) NOT NULL, logo_url VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, contact_phone VARCHAR(20) DEFAULT NULL, INDEX IDX_288A3A4E156BE243 (recruiter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, conversation_id INT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, job_application_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_read TINYINT(1) NOT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), INDEX IDX_B6BD307FF624B39D (sender_id), INDEX IDX_B6BD307FE92F8F78 (recipient_id), INDEX IDX_B6BD307FAC7A5A08 (job_application_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', link VARCHAR(255) DEFAULT NULL, type VARCHAR(50) NOT NULL, entity_type VARCHAR(50) DEFAULT NULL, entity_id INT DEFAULT NULL, actor_id INT DEFAULT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, original_post_id INT DEFAULT NULL, author_id INT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, reaction_counts JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', mentions JSON NOT NULL, likes_counter INT DEFAULT 0 NOT NULL, comments_counter INT DEFAULT 0 NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, INDEX IDX_5A8A6C8DCD09ADDB (original_post_id), INDEX IDX_5A8A6C8DF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE post_hashtag (post_id INT NOT NULL, hashtag_id INT NOT NULL, INDEX IDX_675D9D524B89032C (post_id), INDEX IDX_675D9D52FB34EF56 (hashtag_id), PRIMARY KEY(post_id, hashtag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE post_comment (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, author_id INT NOT NULL, parent_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_A99CE55F4B89032C (post_id), INDEX IDX_A99CE55FF675F31B (author_id), INDEX IDX_A99CE55F727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE post_like (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, post_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', reaction_type VARCHAR(20) NOT NULL, INDEX IDX_653627B8A76ED395 (user_id), INDEX IDX_653627B84B89032C (post_id), UNIQUE INDEX unique_like (user_id, post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE recruiter_subscription (id INT AUTO_INCREMENT NOT NULL, recruiter_id INT NOT NULL, subscription_id INT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, payment_status VARCHAR(50) NOT NULL, remaining_job_offers INT DEFAULT NULL, cancelled TINYINT(1) NOT NULL, auto_renew TINYINT(1) DEFAULT 1 NOT NULL, stripe_subscription_id VARCHAR(255) DEFAULT NULL, INDEX IDX_B74563A3156BE243 (recruiter_id), INDEX IDX_B74563A39A1887DC (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, price DOUBLE PRECISION NOT NULL, duration INT NOT NULL, features JSON NOT NULL, max_job_offers INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE support_ticket (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, priority VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, replies JSON DEFAULT NULL, INDEX IDX_1F5A4D53A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_verified TINYINT(1) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, job_title VARCHAR(255) DEFAULT NULL, experience LONGTEXT DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, skills JSON DEFAULT NULL, resume VARCHAR(255) DEFAULT NULL, documents JSON DEFAULT NULL, discr VARCHAR(255) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, company_description LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, technical_skills JSON DEFAULT NULL, soft_skills JSON DEFAULT NULL, cv_filename VARCHAR(255) DEFAULT NULL, education_history LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', work_experience LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE recruiter_applicant (recruiter_id INT NOT NULL, applicant_id INT NOT NULL, INDEX IDX_B2E376EB156BE243 (recruiter_id), INDEX IDX_B2E376EB97139001 (applicant_id), PRIMARY KEY(recruiter_id, applicant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE applicant_job_offer (applicant_id INT NOT NULL, job_offer_id INT NOT NULL, INDEX IDX_4B48419897139001 (applicant_id), INDEX IDX_4B4841983481D195 (job_offer_id), PRIMARY KEY(applicant_id, job_offer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE work_experience (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, start_date VARCHAR(10) NOT NULL, end_date VARCHAR(10) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_1EF36CD0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE application ADD CONSTRAINT FK_A45BDDC197139001 FOREIGN KEY (applicant_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE application ADD CONSTRAINT FK_A45BDDC13481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9B29A9963 FOREIGN KEY (participant1_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A02F368D FOREIGN KEY (participant2_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9AC7A5A08 FOREIGN KEY (job_application_id) REFERENCES job_application (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE education ADD CONSTRAINT FK_DB0A5ED2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED93481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED991BD8781 FOREIGN KEY (candidate_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE friendship ADD CONSTRAINT FK_7234A45FED442CF4 FOREIGN KEY (requester_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE friendship ADD CONSTRAINT FK_7234A45F2261B4C3 FOREIGN KEY (addressee_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE interview ADD CONSTRAINT FK_CF1D3C34156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE interview ADD CONSTRAINT FK_CF1D3C3497139001 FOREIGN KEY (applicant_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE interview ADD CONSTRAINT FK_CF1D3C343481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_application ADD CONSTRAINT FK_C737C68897139001 FOREIGN KEY (applicant_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_application ADD CONSTRAINT FK_C737C6883481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_offer ADD CONSTRAINT FK_288A3A4E156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD CONSTRAINT FK_B6BD307FAC7A5A08 FOREIGN KEY (job_application_id) REFERENCES job_application (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DCD09ADDB FOREIGN KEY (original_post_id) REFERENCES post (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_hashtag ADD CONSTRAINT FK_675D9D524B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_hashtag ADD CONSTRAINT FK_675D9D52FB34EF56 FOREIGN KEY (hashtag_id) REFERENCES hashtag (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55F4B89032C FOREIGN KEY (post_id) REFERENCES post (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55FF675F31B FOREIGN KEY (author_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55F727ACA70 FOREIGN KEY (parent_id) REFERENCES post_comment (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_like ADD CONSTRAINT FK_653627B8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_like ADD CONSTRAINT FK_653627B84B89032C FOREIGN KEY (post_id) REFERENCES post (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_subscription ADD CONSTRAINT FK_B74563A3156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_subscription ADD CONSTRAINT FK_B74563A39A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_ticket ADD CONSTRAINT FK_1F5A4D53A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_applicant ADD CONSTRAINT FK_B2E376EB156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_applicant ADD CONSTRAINT FK_B2E376EB97139001 FOREIGN KEY (applicant_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer ADD CONSTRAINT FK_4B48419897139001 FOREIGN KEY (applicant_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer ADD CONSTRAINT FK_4B4841983481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work_experience ADD CONSTRAINT FK_1EF36CD0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC197139001
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC13481D195
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9B29A9963
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A02F368D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9AC7A5A08
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE education DROP FOREIGN KEY FK_DB0A5ED2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED93481D195
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED991BD8781
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE friendship DROP FOREIGN KEY FK_7234A45FED442CF4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE friendship DROP FOREIGN KEY FK_7234A45F2261B4C3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE interview DROP FOREIGN KEY FK_CF1D3C34156BE243
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE interview DROP FOREIGN KEY FK_CF1D3C3497139001
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE interview DROP FOREIGN KEY FK_CF1D3C343481D195
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_application DROP FOREIGN KEY FK_C737C68897139001
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_application DROP FOREIGN KEY FK_C737C6883481D195
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE job_offer DROP FOREIGN KEY FK_288A3A4E156BE243
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE92F8F78
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FAC7A5A08
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DCD09ADDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_hashtag DROP FOREIGN KEY FK_675D9D524B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_hashtag DROP FOREIGN KEY FK_675D9D52FB34EF56
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55F4B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55FF675F31B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55F727ACA70
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_like DROP FOREIGN KEY FK_653627B8A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_like DROP FOREIGN KEY FK_653627B84B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_subscription DROP FOREIGN KEY FK_B74563A3156BE243
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_subscription DROP FOREIGN KEY FK_B74563A39A1887DC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE support_ticket DROP FOREIGN KEY FK_1F5A4D53A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_applicant DROP FOREIGN KEY FK_B2E376EB156BE243
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_applicant DROP FOREIGN KEY FK_B2E376EB97139001
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer DROP FOREIGN KEY FK_4B48419897139001
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applicant_job_offer DROP FOREIGN KEY FK_4B4841983481D195
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work_experience DROP FOREIGN KEY FK_1EF36CD0A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE application
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE conversation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE education
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE favorite
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE friendship
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE hashtag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE interview
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE job_application
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE job_offer
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE message
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_hashtag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_comment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_like
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE recruiter_subscription
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE subscription
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE support_ticket
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE recruiter_applicant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE applicant_job_offer
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE work_experience
        SQL);
    }
}
