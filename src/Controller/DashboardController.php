<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\ApplicationRepository;
use App\Repository\InterviewRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\JobOfferRepository;
use App\Repository\PostRepository;
use App\Service\MatchingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(
        JobOfferRepository $jobOfferRepository,
        ApplicationRepository $applicationRepo,
        JobApplicationRepository $jobApplicationRepo,
        InterviewRepository $interviewRepository
    ): Response {
        // Pour les recruteurs
        if ($this->isGranted('ROLE_RECRUTEUR')) {
            // Récupérer uniquement les offres du recruteur connecté
            $allOffers = $jobOfferRepository->findBy(['recruiter' => $this->getUser()]);

            // Séparer les offres actives des offres expirées
            $activeOffers  = [];
            $expiredOffers = [];

            foreach ($allOffers as $offer) {
                if ($offer->getIsActive()) {
                    $activeOffers[] = $offer;
                    continue; // Offer is active, move to next iteration
                }
                // If not active, it's expired
                $expiredOffers[] = $offer;
            }

            // Applications depuis l'ancien système
            $applications = $applicationRepo->findByRecruiter($this->getUser());
            // Applications depuis le nouveau système
            $jobApplications = $jobApplicationRepo->findByRecruiter($this->getUser());

            $recentApplications = \array_slice($applications, 0, 5); // Récupère les 5 candidatures les plus récentes

            // Calcul du nombre de candidatures reçues pour les offres de ce recruteur uniquement
            $totalApplications = 0;
            foreach ($allOffers as $offer) {
                $totalApplications += $applicationRepo->countByJobOffer($offer)
                + $jobApplicationRepo->countByJobOffer($offer->getId() ?: 0);
            }

            // Récupérer les entretiens à venir
            $upcomingInterviews = $interviewRepository->findUpcomingInterviewsForUser($this->getUser());

            return $this->render('dashboard/recruiter.html.twig', [
                'activeOffers'       => $activeOffers,
                'expiredOffers'      => $expiredOffers,
                'recentApplications' => $recentApplications,
                'totalApplications'  => $totalApplications,
                'upcomingInterviews' => $upcomingInterviews,
                'jobApplications'    => $jobApplications,
            ]);
        }

        // Pour les postulants
        if ($this->isGranted('ROLE_POSTULANT')) {
            // Applications depuis l'ancien système
            $applications = $applicationRepo->findByUser($this->getUser());
            // Applications depuis le nouveau système
            $jobApplications = $jobApplicationRepo->findBy(['applicant' => $this->getUser()]);

            // Récupérer les entretiens à venir pour le candidat
            $upcomingInterviews = $interviewRepository->findUpcomingInterviewsForUser($this->getUser());

            return $this->render('dashboard/applicant.html.twig', [
                'applications'       => $applications,
                'jobApplications'    => $jobApplications,
                'upcomingInterviews' => $upcomingInterviews,
            ]);
        }

        // Redirection par défaut
        return $this->redirectToRoute('app_home');
    }

    #[Route('/posts', name: 'app_dashboard_posts')]
    public function posts(PostRepository $postRepository): Response
    {
        return $this->render('dashboard/posts.html.twig', [
            'posts' => $postRepository->findBy(['author' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/applications', name: 'app_dashboard_applications')]
    public function applications(JobApplicationRepository $applicationRepo): Response
    {
        $applications = [];

        if ($this->isGranted('ROLE_POSTULANT')) {
            $applications = $applicationRepo->findBy(['applicant' => $this->getUser()]);
        } elseif ($this->isGranted('ROLE_RECRUTEUR')) {
            $applications = $applicationRepo->findByRecruiter($this->getUser());
        }

        return $this->render('dashboard/applications.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/offers', name: 'app_dashboard_offers')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function offers(JobOfferRepository $jobOfferRepository): Response
    {
        // Récupérer seulement les offres du recruteur connecté
        $offers = $jobOfferRepository->findBy(['recruiter' => $this->getUser()]);

        return $this->render('dashboard/offers.html.twig', [
            'offers' => $offers,
        ]);
    }

    #[Route('/applicant', name: 'app_dashboard_applicant')]
    public function applicantDashboard(
        ApplicationRepository $applicationRepo,
        PostRepository $postRepository,
        MatchingService $matchingService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $applications = $applicationRepo->findByUser($user);
            $posts        = $postRepository->findBy(['author' => $user], ['createdAt' => 'DESC'], 5);

            // Récupérer les offres recommandées
            $suggestedOffers = $matchingService->findBestJobOffersForCandidate($user, 3);

            return $this->render('dashboard/applicant.html.twig', [
                'applications'    => $applications,
                'posts'           => $posts,
                'suggestedOffers' => $suggestedOffers,
            ]);
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_home');
        }
    }
}
