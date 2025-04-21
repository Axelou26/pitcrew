<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320NotificationIndex extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un index sur les colonnes user_id et isRead de la table notification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_notification_user_read ON notification (user_id, is_read)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_notification_user_read ON notification');
    }
} 