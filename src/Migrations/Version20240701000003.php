<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240701000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des index pour optimiser les requêtes de mentions et hashtags';
    }

    public function up(Schema $schema): void
    {
        // Index pour optimiser la recherche par prénom/nom pour les mentions
        $this->addSql('CREATE INDEX idx_user_names ON user (LOWER(first_name), LOWER(last_name))');

        // Index pour optimiser la recherche de hashtags
        $this->addSql('CREATE INDEX idx_hashtag_name ON hashtag (LOWER(name))');

        // Index pour optimiser la recherche de posts par date (pour le feed)
        $this->addSql('CREATE INDEX idx_post_created_at ON post (created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_user_names');
        $this->addSql('DROP INDEX IF EXISTS idx_hashtag_name');
        $this->addSql('DROP INDEX IF EXISTS idx_post_created_at');
    }
}
