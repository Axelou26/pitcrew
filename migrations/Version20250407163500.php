<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250407163500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(180) NOT NULL,
                roles JSON NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                company VARCHAR(255) DEFAULT NULL,
                bio LONGTEXT DEFAULT NULL,
                profile_picture VARCHAR(255) DEFAULT NULL,
                stripe_customer_id VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                skills JSON DEFAULT NULL,
                cv VARCHAR(255) DEFAULT NULL,
                documents JSON DEFAULT NULL,
                job_title VARCHAR(255) DEFAULT NULL,
                experience VARCHAR(255) DEFAULT NULL,
                education VARCHAR(255) DEFAULT NULL,
                city VARCHAR(255) DEFAULT NULL,
                is_verified TINYINT(1) NOT NULL,
                discr VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
    }
}
