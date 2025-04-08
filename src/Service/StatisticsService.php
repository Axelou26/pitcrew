<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\JobApplicationRepository;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    private $jobOfferRepository;
    private $jobApplicationRepository;
    private $entityManager;

    public function __construct(
        JobOfferRepository $jobOfferRepository,
        JobApplicationRepository $jobApplicationRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->jobOfferRepository = $jobOfferRepository;
        $this->jobApplicationRepository = $jobApplicationRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Obtenir les statistiques de base pour un recruteur
     * Disponible pour les abonnements Premium et Business
     */
    public function getBasicStatistics(User $recruiter): array
    {
        // Nombre total d'offres publiées
        $totalOffers = $this->jobOfferRepository->count(['recruiter' => $recruiter]);

        // Nombre d'offres actives
        $activeOffers = $this->jobOfferRepository->count(['recruiter' => $recruiter, 'isActive' => true]);

        // Nombre total de candidatures reçues
        $totalApplications = $this->jobApplicationRepository->countForRecruiter($recruiter);

        // Nombre moyen de candidatures par offre
        $averageApplications = $totalOffers > 0 ? $totalApplications / $totalOffers : 0;

        // Taux de conversion (candidatures / vues)
        $conversionRate = $this->calculateConversionRate($recruiter);

        return [
            'totalOffers' => $totalOffers,
            'activeOffers' => $activeOffers,
            'totalApplications' => $totalApplications,
            'averageApplications' => round($averageApplications, 1),
            'conversionRate' => $conversionRate
        ];
    }

    /**
     * Obtenir des statistiques détaillées pour un recruteur
     * Disponible uniquement pour l'abonnement Business
     */
    public function getDetailedStatistics(User $recruiter): array
    {
        // Récupérer les statistiques de base
        $basicStats = $this->getBasicStatistics($recruiter);

        // Statistiques par catégorie d'offre
        $statsByCategory = $this->getStatsByCategory($recruiter);

        // Évolution mensuelle des candidatures (6 derniers mois)
        $monthlyApplications = $this->getMonthlyApplicationsStats($recruiter);

        // Top 5 des offres avec le plus de candidatures
        $topOffers = $this->getTopOffersByApplications($recruiter);

        // Provenance des candidats (LinkedIn, site web, etc.)
        $applicationsSources = $this->getApplicationsSources($recruiter);

        // Répartition des candidats par compétences
        $skillsDistribution = $this->getSkillsDistribution($recruiter);

        return array_merge($basicStats, [
            'statsByCategory' => $statsByCategory,
            'monthlyApplications' => $monthlyApplications,
            'topOffers' => $topOffers,
            'applicationsSources' => $applicationsSources,
            'skillsDistribution' => $skillsDistribution
        ]);
    }

    /**
     * Calculer le taux de conversion (candidatures / vues)
     */
    private function calculateConversionRate(User $recruiter): float
    {
        $totalViews = $this->jobOfferRepository->getTotalViewsForRecruiter($recruiter);
        $totalApplications = $this->jobApplicationRepository->countForRecruiter($recruiter);

        if ($totalViews > 0) {
            return round(($totalApplications / $totalViews) * 100, 1);
        }

        return 0;
    }

    /**
     * Obtenir les statistiques par catégorie d'offre
     */
    private function getStatsByCategory(User $recruiter): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = '
            SELECT 
                jo.category, 
                COUNT(jo.id) as total_offers,
                SUM(CASE WHEN jo.is_active = 1 THEN 1 ELSE 0 END) as active_offers,
                COUNT(ja.id) as applications
            FROM job_offer jo
            LEFT JOIN job_application ja ON ja.job_offer_id = jo.id
            WHERE jo.recruiter_id = :recruiterId
            GROUP BY jo.category
            ORDER BY applications DESC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['recruiterId' => $recruiter->getId()]);

        return $result->fetchAllAssociative();
    }

    /**
     * Obtenir l'évolution mensuelle des candidatures
     */
    private function getMonthlyApplicationsStats(User $recruiter): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = '
            SELECT 
                DATE_FORMAT(ja.created_at, "%Y-%m") as month,
                COUNT(ja.id) as applications
            FROM job_application ja
            JOIN job_offer jo ON ja.job_offer_id = jo.id
            WHERE jo.recruiter_id = :recruiterId
            AND ja.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['recruiterId' => $recruiter->getId()]);

        return $result->fetchAllAssociative();
    }

    /**
     * Obtenir le top 5 des offres avec le plus de candidatures
     */
    private function getTopOffersByApplications(User $recruiter): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = '
            SELECT 
                jo.id,
                jo.title,
                COUNT(ja.id) as applications
            FROM job_offer jo
            LEFT JOIN job_application ja ON ja.job_offer_id = jo.id
            WHERE jo.recruiter_id = :recruiterId
            GROUP BY jo.id
            ORDER BY applications DESC
            LIMIT 5
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['recruiterId' => $recruiter->getId()]);

        return $result->fetchAllAssociative();
    }

    /**
     * Obtenir les sources des candidatures
     */
    private function getApplicationsSources(User $recruiter): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = '
            SELECT 
                ja.source,
                COUNT(ja.id) as count
            FROM job_application ja
            JOIN job_offer jo ON ja.job_offer_id = jo.id
            WHERE jo.recruiter_id = :recruiterId
            GROUP BY ja.source
            ORDER BY count DESC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['recruiterId' => $recruiter->getId()]);

        return $result->fetchAllAssociative();
    }

    /**
     * Obtenir la répartition des candidats par compétences
     */
    private function getSkillsDistribution(User $recruiter): array
    {
        // Cette fonction nécessiterait une structure de données spécifique
        // pour stocker les compétences des candidats. Pour cet exemple,
        // nous renvoyons des données simulées.

        return [
            ['skill' => 'PHP', 'count' => 75],
            ['skill' => 'JavaScript', 'count' => 68],
            ['skill' => 'Symfony', 'count' => 52],
            ['skill' => 'React', 'count' => 45],
            ['skill' => 'Docker', 'count' => 35],
            ['skill' => 'DevOps', 'count' => 28],
            ['skill' => 'UX/UI', 'count' => 25]
        ];
    }
}
