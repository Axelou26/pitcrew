<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ApplicationRepository;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    private JobOfferRepository $jobOfferRepository;
    private ApplicationRepository $appRepo;
    private EntityManagerInterface $entityManager;

    public function __construct(
        JobOfferRepository $jobOfferRepository,
        ApplicationRepository $appRepo,
        EntityManagerInterface $entityManager
    ) {
        $this->jobOfferRepository = $jobOfferRepository;
        $this->appRepo            = $appRepo;
        $this->entityManager      = $entityManager;
    }

    /**
     * Récupère les statistiques de base.
     *
     * @return array<string, mixed>
     */
    public function getBasicStatistics(): array
    {
        // Nombre total d'offres publiées
        $totalJobOffers = $this->jobOfferRepository->count([]);

        // Nombre total de candidatures
        $totalApplications = $this->appRepo->count([]);

        // Nombre d'offres actives
        $activeJobOffers = $this->jobOfferRepository->count(['isActive' => true]);

        // Taux de conversion (candidatures par offre)
        $conversionRate = $totalJobOffers > 0 ? round(($totalApplications / $totalJobOffers) * 100, 2) : 0;

        return [
            'totalJobOffers'    => $totalJobOffers,
            'totalApplications' => $totalApplications,
            'activeJobOffers'   => $activeJobOffers,
            'conversionRate'    => $conversionRate,
        ];
    }

    /**
     * Récupère les statistiques détaillées.
     *
     * @return array<string, mixed>
     */
    public function getDetailedStatistics(): array
    {
        // Récupérer les statistiques de base
        $basicStats = $this->getBasicStatistics();

        // Statistiques par catégorie
        $categoryStats = $this->getStatsByCategory();

        // Statistiques mensuelles
        $monthlyStats = $this->getMonthlyApplicationsStats();

        // Top des offres
        $topOffers = $this->getTopOffersByApplications();

        // Sources des candidatures
        $sources = $this->getApplicationsSources();

        // Distribution des compétences
        $skillsDistribution = $this->getSkillsDistribution();

        return array_merge($basicStats, [
            'categoryStats'      => $categoryStats,
            'monthlyStats'       => $monthlyStats,
            'topOffers'          => $topOffers,
            'sources'            => $sources,
            'skillsDistribution' => $skillsDistribution,
        ]);
    }

    /**
     * Récupère les statistiques par catégorie.
     *
     * @return array<string, mixed>
     */
    private function getStatsByCategory(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('jo.category, COUNT(jo.id) as count')
            ->from('App\Entity\JobOffer', 'jo')
            ->groupBy('jo.category');

        $result = $qb->getQuery()->getResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[$row['category']] = $row['count'];
        }

        return $stats;
    }

    /**
     * Récupère les statistiques mensuelles des candidatures.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMonthlyApplicationsStats(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('SUBSTRING(a.createdAt, 1, 7) as month, COUNT(a.id) as count')
            ->from('App\Entity\Application', 'a')
            ->groupBy('month')
            ->orderBy('month', 'DESC')
            ->setMaxResults(12);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les offres les plus populaires.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTopOffersByApplications(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('jo.title, COUNT(a.id) as applicationCount')
            ->from('App\Entity\JobOffer', 'jo')
            ->leftJoin('jo.applications', 'a')
            ->groupBy('jo.id')
            ->orderBy('applicationCount', 'DESC')
            ->setMaxResults(5);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les sources des candidatures.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getApplicationsSources(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a.source, COUNT(a.id) as count')
            ->from('App\Entity\Application', 'a')
            ->groupBy('a.source');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère la distribution des compétences.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSkillsDistribution(): array
    {
        // Cette méthode nécessiterait une analyse plus complexe des compétences
        // Pour l'instant, retournons un tableau vide
        return [];
    }
}
