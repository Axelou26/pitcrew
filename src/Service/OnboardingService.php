<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Applicant;
use App\Entity\JobOffer;
use App\Entity\Recruiter;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class OnboardingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Obtient les recommandations personnalisées pour un utilisateur.
     */
    public function getPersonalizedRecommendations(User $user): array
    {
        $recommendations = [];

        if ($user instanceof Applicant) {
            $recommendations = $this->getApplicantRecommendations($user);
        } elseif ($user instanceof Recruiter) {
            $recommendations = $this->getRecruiterRecommendations($user);
        }

        return $recommendations;
    }

    /**
     * Obtient les étapes d'onboarding pour un utilisateur.
     */
    public function getOnboardingSteps(User $user): array
    {
        $steps = [
            [
                'id'          => 'profile',
                'title'       => 'Compléter le profil',
                'description' => 'Ajoutez vos informations personnelles et professionnelles',
                'url'         => 'app_profile_edit',
                'icon'        => 'fas fa-user-edit',
                'completed'   => $this->isProfileComplete($user),
            ],
        ];

        if ($user instanceof Applicant) {
            $steps[] = [
                'id'          => 'cv',
                'title'       => 'Uploadez votre CV',
                'description' => 'Ajoutez votre CV pour faciliter les candidatures',
                'url'         => 'app_profile_edit',
                'icon'        => 'fas fa-file-upload',
                'completed'   => !empty($user->getCvFilename()),
            ];

            $steps[] = [
                'id'          => 'skills',
                'title'       => 'Ajouter des compétences',
                'description' => 'Listez vos compétences techniques et soft skills',
                'url'         => 'app_profile_edit',
                'icon'        => 'fas fa-tools',
                'completed'   => !empty($user->getTechnicalSkills()),
            ];
        } elseif ($user instanceof Recruiter) {
            $steps[] = [
                'id'          => 'company',
                'title'       => 'Configurer l\'entreprise',
                'description' => 'Ajoutez les informations de votre entreprise',
                'url'         => 'app_recruiter_profile',
                'icon'        => 'fas fa-building',
                'completed'   => !empty($user->getCompanyDescription()),
            ];

            $steps[] = [
                'id'          => 'job_offer',
                'title'       => 'Publier une offre',
                'description' => 'Créez votre première offre d\'emploi',
                'url'         => 'app_job_offer_new',
                'icon'        => 'fas fa-plus',
                'completed'   => !$user->getJobOffers()->isEmpty(),
            ];
        }

        $steps[] = [
            'id'          => 'discover',
            'title'       => 'Découvrir la plateforme',
            'description' => 'Explorez toutes les fonctionnalités disponibles',
            'url'         => 'app_dashboard',
            'icon'        => 'fas fa-compass',
            'completed'   => false,
        ];

        return $steps;
    }

    /**
     * Obtient les statistiques d'onboarding.
     */
    public function getOnboardingStats(): array
    {
        $totalUsers    = $this->entityManager->getRepository(User::class)->count([]);
        $verifiedUsers = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isVerified = :verified')
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();

        $completeProfiles = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isVerified = :verified')
            ->andWhere('u.jobTitle IS NOT NULL')
            ->andWhere('u.city IS NOT NULL')
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_users'       => $totalUsers,
            'verified_users'    => $verifiedUsers,
            'complete_profiles' => $completeProfiles,
            'verification_rate' => $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100, 1) : 0,
            'completion_rate'   => $verifiedUsers > 0 ? round(($completeProfiles / $verifiedUsers) * 100, 1) : 0,
        ];
    }

    /**
     * Obtient les recommandations pour un postulant.
     */
    private function getApplicantRecommendations(Applicant $applicant): array
    {
        $recommendations = [];

        // Recommandations basées sur les compétences
        $skills = $applicant->getTechnicalSkills();
        if (!empty($skills)) {
            $jobOffers = $this->entityManager->getRepository(JobOffer::class)
                ->findBySkills($skills, 5);

            if (!empty($jobOffers)) {
                $recommendations['job_offers'] = [
                    'title'       => 'Offres d\'emploi recommandées',
                    'description' => 'Basées sur vos compétences',
                    'items'       => $jobOffers,
                    'action'      => [
                        'text' => 'Voir toutes les offres',
                        'url'  => 'app_job_offer_index',
                    ],
                ];
            }
        }

        // Recommandations pour compléter le profil
        $profileCompleteness = $this->calculateProfileCompleteness($applicant);
        if ($profileCompleteness < 80) {
            $recommendations['profile_completion'] = [
                'title'       => 'Complétez votre profil',
                'description' => 'Votre profil est complété à ' . $profileCompleteness . '%',
                'progress'    => $profileCompleteness,
                'suggestions' => $this->getProfileCompletionSuggestions($applicant),
                'action'      => [
                    'text' => 'Modifier le profil',
                    'url'  => 'app_profile_edit',
                ],
            ];
        }

        // Recommandations de compétences
        $recommendations['skills'] = [
            'title'       => 'Compétences recherchées',
            'description' => 'Compétences populaires dans le secteur',
            'items'       => [
                'Mécanique F1',
                'Aérodynamique',
                'Électronique embarquée',
                'Simulation',
                'Gestion de projet',
            ],
            'action' => [
                'text' => 'Ajouter des compétences',
                'url'  => 'app_profile_edit',
            ],
        ];

        return $recommendations;
    }

    /**
     * Obtient les recommandations pour un recruteur.
     */
    private function getRecruiterRecommendations(Recruiter $recruiter): array
    {
        $recommendations = [];

        // Recommandations pour l'entreprise
        if (empty($recruiter->getCompanyDescription())) {
            $recommendations['company_profile'] = [
                'title'       => 'Complétez le profil entreprise',
                'description' => 'Ajoutez une description pour attirer les candidats',
                'action'      => [
                    'text' => 'Modifier le profil entreprise',
                    'url'  => 'app_recruiter_profile',
                ],
            ];
        }

        // Recommandations pour publier une offre
        $jobOffers = $recruiter->getJobOffers();
        if ($jobOffers->isEmpty()) {
            $recommendations['first_job_offer'] = [
                'title'       => 'Publiez votre première offre',
                'description' => 'Commencez à attirer des candidats qualifiés',
                'action'      => [
                    'text' => 'Créer une offre',
                    'url'  => 'app_job_offer_new',
                ],
            ];
        }

        // Statistiques du marché
        $recommendations['market_stats'] = [
            'title'       => 'Tendances du marché',
            'description' => 'Informations sur le secteur',
            'items'       => [
                'Demande élevée pour les ingénieurs F1',
                'Compétences en simulation très recherchées',
                'Salaires en hausse de 15% cette année',
            ],
        ];

        return $recommendations;
    }

    /**
     * Calcule le pourcentage de complétude du profil.
     */
    private function calculateProfileCompleteness(User $user): int
    {
        $fields = [
            'firstName' => 10,
            'lastName'  => 10,
            'email'     => 10,
            'city'      => 5,
            'jobTitle'  => 10,
        ];

        if ($user instanceof Applicant) {
            $fields['location']        = 10;
            $fields['technicalSkills'] = 15;
            $fields['description']     = 10;
            $fields['cvFilename']      = 10;
        } elseif ($user instanceof Recruiter) {
            $fields['companyName']        = 15;
            $fields['companyDescription'] = 15;
            $fields['website']            = 5;
        }

        $totalScore = 0;
        $maxScore   = array_sum($fields);

        foreach ($fields as $field => $score) {
            $getter = 'get' . ucfirst($field);
            if (method_exists($user, $getter)) {
                $value = $user->$getter();
                if (!empty($value)) {
                    $totalScore += $score;
                }
            }
        }

        return (int) round(($totalScore / $maxScore) * 100);
    }

    /**
     * Obtient les suggestions pour compléter le profil.
     */
    private function getProfileCompletionSuggestions(Applicant $applicant): array
    {
        $suggestions = [];

        if (empty($applicant->getLocation())) {
            $suggestions[] = 'Ajoutez votre localisation pour être visible dans les recherches';
        }

        if (empty($applicant->getTechnicalSkills())) {
            $suggestions[] = 'Listez vos compétences techniques pour améliorer votre visibilité';
        }

        if (empty($applicant->getDescription())) {
            $suggestions[] = 'Rédigez une présentation personnelle pour vous démarquer';
        }

        if (empty($applicant->getCvFilename())) {
            $suggestions[] = 'Uploadez votre CV pour faciliter les candidatures';
        }

        return $suggestions;
    }

    /**
     * Vérifie si le profil de base est complet.
     */
    private function isProfileComplete(User $user): bool
    {
        return !empty($user->getFirstName()) &&
               !empty($user->getLastName()) &&
               !empty($user->getEmail()) &&
               !empty($user->getJobTitle());
    }
}
