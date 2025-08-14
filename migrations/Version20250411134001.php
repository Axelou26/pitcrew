<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250411134001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime la colonne reaction_type de la table post_like pour simplifier le système de likes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post_like DROP COLUMN reaction_type');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post_like ADD reaction_type VARCHAR(20) NOT NULL DEFAULT \'like\'');
    }
}
