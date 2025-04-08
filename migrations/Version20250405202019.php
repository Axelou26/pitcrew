<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250405202019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
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
            ALTER TABLE post_share DROP FOREIGN KEY FK_781D11B56A5E0F9B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_781D11B56A5E0F9B ON post_share
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_share DROP shared_post_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD preferred_contract_types JSON DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE recruiter_user (recruiter_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_71AE4A26156BE243 (recruiter_id), INDEX IDX_71AE4A26A76ED395 (user_id), PRIMARY KEY(recruiter_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_user ADD CONSTRAINT FK_71AE4A26156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE recruiter_user ADD CONSTRAINT FK_71AE4A26A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_share ADD shared_post_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_share ADD CONSTRAINT FK_781D11B56A5E0F9B FOREIGN KEY (shared_post_id) REFERENCES post (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_781D11B56A5E0F9B ON post_share (shared_post_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP preferred_contract_types
        SQL);
    }
}
