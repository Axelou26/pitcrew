<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour optimiser les performances de la table friendship
 */
final class Version20250410164853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout d\'index pour optimiser les performances de la table friendship';
    }

    public function up(Schema $schema): void
    {
        // Index pour optimiser les requêtes de demandes d'amitié reçues
        $this->addSql('CREATE INDEX idx_friendship_addressee_status ON friendship (addressee_id, status)');
        
        // Index pour optimiser les requêtes de demandes d'amitié envoyées
        $this->addSql('CREATE INDEX idx_friendship_requester_status ON friendship (requester_id, status)');
        
        // Index composite pour optimiser les requêtes entre deux utilisateurs
        $this->addSql('CREATE INDEX idx_friendship_users_status ON friendship (requester_id, addressee_id, status)');
        
        // Index pour optimiser les requêtes par date de création
        $this->addSql('CREATE INDEX idx_friendship_created_at ON friendship (created_at)');
        
        // Index pour optimiser les requêtes par date de mise à jour
        $this->addSql('CREATE INDEX idx_friendship_updated_at ON friendship (updated_at)');
    }

    public function down(Schema $schema): void
    {
        // Suppression des index ajoutés
        $this->addSql('DROP INDEX idx_friendship_addressee_status ON friendship');
        $this->addSql('DROP INDEX idx_friendship_requester_status ON friendship');
        $this->addSql('DROP INDEX idx_friendship_users_status ON friendship');
        $this->addSql('DROP INDEX idx_friendship_created_at ON friendship');
        $this->addSql('DROP INDEX idx_friendship_updated_at ON friendship');
    }
} 