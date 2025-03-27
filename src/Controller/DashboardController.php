<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\JobOffer;
use App\Repository\JobOfferRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\InterviewRepository;
use App\Service\MatchingService;
use App\Entity\User;
use App\Entity\Applicant;

#[Route('/dashboard')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(
        JobOfferRepository $jobOfferRepository,
        JobApplicationRepository $jobApplicationRepository,
        InterviewRepository $interviewRepository
    ): Response {
        // Pour les recruteurs
        if ($this->isGranted('ROLE_RECRUTEUR')) {
            // Récupérer uniquement les offres du recruteur connecté
            $allOffers = $jobOfferRepository->findByRecruiter($this->getUser());
            
            // Séparer les offres actives des offres expirées
            $activeOffers = [];
            $expiredOffers = [];
            
            foreach ($allOffers as $offer) {
                if ($offer->getIsActive()) {
                    $activeOffers[] = $offer;
                } else {
                    $expiredOffers[] = $offer;
                }
            }
            
            $applications = $jobApplicationRepository->findByRecruiter($this->getUser());
            $recentApplications = array_slice($applications, 0, 5); // Récupère les 5 candidatures les plus récentes
            $totalApplications = count($applications);
            
            // Récupérer les entretiens à venir
            $upcomingInterviews = $interviewRepository->findUpcomingInterviewsForUser($this->getUser());

            return $this->render('dashboard/recruiter.html.twig', [
                'activeOffers' => $activeOffers,
                'expiredOffers' => $expiredOffers,
                'recentApplications' => $recentApplications,
                'totalApplications' => $totalApplications,
                'upcomingInterviews' => $upcomingInterviews
            ]);
        }
        
        // Pour les postulants
        if ($this->isGranted('ROLE_POSTULANT')) {
            $applications = $jobApplicationRepository->findBy(['applicant' => $this->getUser()]);
            
            // Récupérer les entretiens à venir pour le candidat
            $upcomingInterviews = $interviewRepository->findUpcomingInterviewsForUser($this->getUser());
            
            return $this->render('dashboard/applicant.html.twig', [
                'applications' => $applications,
                'upcomingInterviews' => $upcomingInterviews
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
    public function applications(): Response
    {
        return $this->render('dashboard/applications.html.twig', [
            'applications' => [],
        ]);
    }

    #[Route('/offers', name: 'app_dashboard_offers')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function offers(JobOfferRepository $jobOfferRepository): Response
    {
        // Récupérer seulement les offres du recruteur connecté
        $offers = $jobOfferRepository->findByRecruiter($this->getUser());
        
        return $this->render('dashboard/offers.html.twig', [
            'offers' => $offers,
        ]);
    }

    #[Route('/applicant', name: 'app_dashboard_applicant')]
    public function applicantDashboard(
        JobApplicationRepository $jobApplicationRepository,
        PostRepository $postRepository,
        MatchingService $matchingService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        try {
            $applications = $jobApplicationRepository->findBy(['applicant' => $user], ['createdAt' => 'DESC']);
            $posts = $postRepository->findBy(['author' => $user], ['createdAt' => 'DESC'], 5);
            
            // Récupérer les offres recommandées
            $suggestedOffers = $matchingService->findBestJobOffersForCandidate($user, 3);
            
            return $this->render('dashboard/applicant.html.twig', [
                'applications' => $applications,
                'posts' => $posts,
                'suggestedOffers' => $suggestedOffers
            ]);
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_home');
        }
    }
} 