<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250420000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialise les champs JSON null avec des tableaux vides pour les entités Applicant';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour les enregistrements existants pour remplacer les valeurs NULL par des tableaux vides
        $this->addSql("UPDATE `user` SET technical_skills = '[]' WHERE technical_skills IS NULL AND discr = 'applicant'");
        $this->addSql("UPDATE `user` SET soft_skills = '[]' WHERE soft_skills IS NULL AND discr = 'applicant'");
        $this->addSql("UPDATE `user` SET education_history = '[]' WHERE education_history IS NULL AND discr = 'applicant'");
        $this->addSql("UPDATE `user` SET work_experience = '[]' WHERE work_experience IS NULL AND discr = 'applicant'");
    }

    public function down(Schema $schema): void
    {
        // Cette migration n'est pas réversible car nous ne pouvons pas savoir quelles données étaient NULL à l'origine
    }
}
