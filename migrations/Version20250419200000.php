<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Création de la table user_job_offer pour les favoris de tous les utilisateurs.
 */
final class Version20250419200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table user_job_offer pour les favoris de tous les utilisateurs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_job_offer (
                user_id INT NOT NULL,
                job_offer_id INT NOT NULL,
                INDEX IDX_FD8E1267A76ED395 (user_id),
                INDEX IDX_FD8E12673481D195 (job_offer_id),
                PRIMARY KEY(user_id, job_offer_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_job_offer
            ADD CONSTRAINT FK_FD8E1267A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_FD8E12673481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_job_offer DROP FOREIGN KEY FK_FD8E1267A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_job_offer DROP FOREIGN KEY FK_FD8E12673481D195
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_job_offer
        SQL);
    }
}
