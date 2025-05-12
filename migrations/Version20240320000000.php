<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des index pour optimiser les recherches de mentions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_user_firstname ON user (first_name)');
        $this->addSql('CREATE INDEX IDX_user_lastname ON user (last_name)');
        $this->addSql('CREATE INDEX IDX_user_name_search ON user (first_name, last_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_user_firstname ON user');
        $this->addSql('DROP INDEX IDX_user_lastname ON user');
        $this->addSql('DROP INDEX IDX_user_name_search ON user');
    }
} 