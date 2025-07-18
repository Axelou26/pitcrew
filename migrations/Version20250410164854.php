<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410164854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout d\'index pour optimiser les performances des notifications';
    }

    public function up(Schema $schema): void
    {
        // Ajout d'index pour optimiser les requêtes sur les notifications
        $this->addSql('CREATE INDEX idx_notification_user_read ON notification (user_id, is_read)');
        $this->addSql('CREATE INDEX idx_notification_created_at ON notification (created_at)');
        $this->addSql('CREATE INDEX idx_notification_type ON notification (type)');
    }

    public function down(Schema $schema): void
    {
        // Suppression des index ajoutés
        $this->addSql('DROP INDEX idx_notification_user_read ON notification');
        $this->addSql('DROP INDEX idx_notification_created_at ON notification');
        $this->addSql('DROP INDEX idx_notification_type ON notification');
    }
} 