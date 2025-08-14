<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250725090914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add applicant field to JobApplication entity and other schema changes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applicant_job_offer DROP FOREIGN KEY FK_4B4841983481D195');
        $this->addSql('ALTER TABLE applicant_job_offer DROP FOREIGN KEY FK_4B48419897139001');
        $this->addSql('ALTER TABLE post_hashtag DROP FOREIGN KEY FK_675D9D524B89032C');
        $this->addSql('ALTER TABLE post_hashtag DROP FOREIGN KEY FK_675D9D52FB34EF56');
        $this->addSql('ALTER TABLE recruiter_applicant DROP FOREIGN KEY FK_B2E376EB156BE243');
        $this->addSql('ALTER TABLE recruiter_applicant DROP FOREIGN KEY FK_B2E376EB97139001');
        $this->addSql('DROP TABLE applicant_job_offer');
        $this->addSql('DROP TABLE post_hashtag');
        $this->addSql('DROP TABLE recruiter_applicant');
        $this->addSql('ALTER TABLE friendship CHANGE status status VARCHAR(255) NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE interview ADD meeting_id VARCHAR(255) DEFAULT NULL, CHANGE job_offer_id job_offer_id INT NOT NULL, CHANGE status status VARCHAR(50) NOT NULL');

        // Add applicant_id column to job_application table
        $this->addSql('ALTER TABLE job_application ADD applicant_id INT NOT NULL');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_A45BDDC197139001 FOREIGN KEY (applicant_id) REFERENCES applicant (id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC197139001 ON job_application (applicant_id)');
        $this->addSql('ALTER TABLE job_application CHANGE cover_letter cover_letter LONGTEXT DEFAULT NULL, CHANGE resume resume VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FAC7A5A08');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE92F8F78');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('DROP INDEX IDX_B6BD307FF624B39D ON message');
        $this->addSql('DROP INDEX IDX_B6BD307FE92F8F78 ON message');
        $this->addSql('DROP INDEX IDX_B6BD307FAC7A5A08 ON message');
        $this->addSql('ALTER TABLE message ADD author_id INT NOT NULL, DROP sender_id, DROP recipient_id, DROP job_application_id, DROP is_read, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FF675F31B ON message (author_id)');
        $this->addSql('ALTER TABLE notification CHANGE type type VARCHAR(255) NOT NULL, CHANGE entity_type entity_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_notification_user_read ON notification (user_id, is_read)');
        $this->addSql('CREATE INDEX idx_notification_created_at ON notification (created_at)');
        $this->addSql('CREATE INDEX idx_notification_type ON notification (type)');
        $this->addSql('ALTER TABLE post ADD shares_counter INT DEFAULT 0 NOT NULL, CHANGE reaction_counts image_urls JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE post_like DROP reaction_type');
        $this->addSql('ALTER TABLE recruiter_subscription CHANGE recruiter_id recruiter_id INT DEFAULT NULL, CHANGE subscription_id subscription_id INT DEFAULT NULL, CHANGE start_date start_date DATETIME DEFAULT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL, CHANGE payment_status payment_status VARCHAR(255) NOT NULL, CHANGE auto_renew auto_renew TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE support_ticket CHANGE replies replies JSON NOT NULL');
        $this->addSql('ALTER TABLE user ADD is_active TINYINT(1) DEFAULT NULL, DROP phone, DROP birth_date, DROP experience, CHANGE skills skills JSON NOT NULL, CHANGE documents documents JSON NOT NULL, CHANGE education_history education_history JSON DEFAULT NULL, CHANGE work_experience work_experience JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE applicant_job_offer (applicant_id INT NOT NULL, job_offer_id INT NOT NULL, INDEX IDX_4B48419897139001 (applicant_id), INDEX IDX_4B4841983481D195 (job_offer_id), PRIMARY KEY(applicant_id, job_offer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE post_hashtag (post_id INT NOT NULL, hashtag_id INT NOT NULL, INDEX IDX_675D9D524B89032C (post_id), INDEX IDX_675D9D52FB34EF56 (hashtag_id), PRIMARY KEY(post_id, hashtag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE recruiter_applicant (recruiter_id INT NOT NULL, applicant_id INT NOT NULL, INDEX IDX_B2E376EB97139001 (applicant_id), INDEX IDX_B2E376EB156BE243 (recruiter_id), PRIMARY KEY(recruiter_id, applicant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE applicant_job_offer ADD CONSTRAINT FK_4B4841983481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE applicant_job_offer ADD CONSTRAINT FK_4B48419897139001 FOREIGN KEY (applicant_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_hashtag ADD CONSTRAINT FK_675D9D524B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_hashtag ADD CONSTRAINT FK_675D9D52FB34EF56 FOREIGN KEY (hashtag_id) REFERENCES hashtag (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recruiter_applicant ADD CONSTRAINT FK_B2E376EB156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recruiter_applicant ADD CONSTRAINT FK_B2E376EB97139001 FOREIGN KEY (applicant_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE friendship CHANGE status status VARCHAR(20) NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE interview DROP meeting_id, CHANGE job_offer_id job_offer_id INT DEFAULT NULL, CHANGE status status VARCHAR(20) NOT NULL');

        // Drop the applicant_id column from job_application table
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_A45BDDC197139001');
        $this->addSql('DROP INDEX IDX_A45BDDC197139001 ON job_application');
        $this->addSql('ALTER TABLE job_application DROP applicant_id');
        $this->addSql('ALTER TABLE job_application CHANGE status status VARCHAR(20) NOT NULL, CHANGE cover_letter cover_letter LONGTEXT NOT NULL, CHANGE resume resume VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF675F31B');
        $this->addSql('DROP INDEX IDX_B6BD307FF675F31B ON message');
        $this->addSql('ALTER TABLE message ADD recipient_id INT NOT NULL, ADD job_application_id INT DEFAULT NULL, ADD is_read TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE author_id sender_id INT NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FAC7A5A08 FOREIGN KEY (job_application_id) REFERENCES job_application (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_B6BD307FF624B39D ON message (sender_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FE92F8F78 ON message (recipient_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FAC7A5A08 ON message (job_application_id)');
        $this->addSql('DROP INDEX idx_notification_user_read ON notification');
        $this->addSql('DROP INDEX idx_notification_created_at ON notification');
        $this->addSql('DROP INDEX idx_notification_type ON notification');
        $this->addSql('ALTER TABLE notification CHANGE type type VARCHAR(50) NOT NULL, CHANGE entity_type entity_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE post DROP shares_counter, CHANGE image_urls reaction_counts JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE post_like ADD reaction_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE recruiter_subscription CHANGE recruiter_id recruiter_id INT NOT NULL, CHANGE subscription_id subscription_id INT NOT NULL, CHANGE start_date start_date DATETIME NOT NULL, CHANGE end_date end_date DATETIME NOT NULL, CHANGE payment_status payment_status VARCHAR(50) NOT NULL, CHANGE auto_renew auto_renew TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE support_ticket CHANGE replies replies JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD phone VARCHAR(20) DEFAULT NULL, ADD birth_date DATE DEFAULT NULL, ADD experience LONGTEXT DEFAULT NULL, DROP is_active, CHANGE skills skills JSON DEFAULT NULL, CHANGE documents documents JSON DEFAULT NULL, CHANGE education_history education_history LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE work_experience work_experience LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }
}
