<?php

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Nettoie les abonnements dupliqués et crée 3 abonnements corrects : Basic, Premium, Business';
    }

    public function up(Schema $schema): void
    {
        // Supprimer tous les abonnements existants
        $this->addSql('DELETE FROM subscription');

        // Réinitialiser l'auto-increment
        $this->addSql('ALTER TABLE subscription AUTO_INCREMENT = 1');

        // Insérer les 3 abonnements corrects
        $this->addSql("INSERT INTO subscription (name, price, duration, max_job_offers, features, is_active) VALUES
            ('Basic', 0, 30, 3, '[\"post_job_offer\", \"basic_applications\", \"limited_messaging\", \"standard_profile\"]', 1),
            ('Premium', 49, 30, NULL, '[\"post_job_offer\", \"unlimited_job_offers\", \"highlighted_offers\", \"full_cv_access\", \"unlimited_messaging\", \"basic_statistics\", \"enhanced_profile\"]', 1),
            ('Business', 99, 30, NULL, '[\"post_job_offer\", \"unlimited_job_offers\", \"advanced_candidate_search\", \"automatic_recommendations\", \"detailed_statistics\", \"verified_badge\", \"priority_support\"]', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprimer les 3 abonnements créés
        $this->addSql("DELETE FROM subscription WHERE name IN ('Basic', 'Premium', 'Business')");
    }
}
