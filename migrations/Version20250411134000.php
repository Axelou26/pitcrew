<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250411134000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permet les valeurs nulles dans la colonne title de la table post';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post MODIFY title VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post MODIFY title VARCHAR(255) NOT NULL');
    }
} 