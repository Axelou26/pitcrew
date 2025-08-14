<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter la colonne phone manquante dans la base de données.
 */
final class Version20250727182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne phone à la table user';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne existe déjà
        $table = $schema->getTable('`user`');
        if (!$table->hasColumn('phone')) {
            $this->addSql('ALTER TABLE `user` ADD phone VARCHAR(20) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Vérifier si la colonne existe avant de la supprimer
        $table = $schema->getTable('`user`');
        if ($table->hasColumn('phone')) {
            $this->addSql('ALTER TABLE `user` DROP phone');
        }
    }
}
