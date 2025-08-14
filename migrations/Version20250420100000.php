<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250420100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialise les champs booléens null avec des valeurs par défaut';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour le champ is_active qui est null
        $this->addSql("UPDATE `user` SET is_active = 1 WHERE is_active IS NULL AND discr = 'applicant'");
    }

    public function down(Schema $schema): void
    {
        // Cette migration n'est pas réversible
    }
}
