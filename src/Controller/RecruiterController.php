<?php

namespace App\Controller;

use App\Entity\JobOffer;
use App\Form\JobOfferType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\SubscriptionService;
use App\Entity\Applicant;
use App\Repository\ApplicantRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\JobOfferRepository;
use App\Service\StatisticsService;

#[Route('/recruiter')]
#[IsGranted('ROLE_RECRUTEUR')]
class RecruiterController extends AbstractController
{
    #[Route('/dashboard', name: 'app_recruiter_dashboard')]
    public function dashboard(
        JobOfferRepository $jobOfferRepository,
        JobApplicationRepository $jobApplicationRepository,
        SubscriptionService $subscriptionService
    ): Response {
        $recruiter = $this->getUser();
        $activeOffers = $jobOfferRepository->findBy(['recruiter' => $recruiter, 'isActive' => true]);
        $expiredOffers = $jobOfferRepository->findBy(['recruiter' => $recruiter, 'isActive' => false]);
        $recentApplications = $jobApplicationRepository->findRecentApplicationsForRecruiter($recruiter, 5);
        
        // Récupérer l'abonnement actif
        $activeSubscription = $subscriptionService->getActiveSubscription($recruiter);
        
        return $this->render('recruiter/dashboard.html.twig', [
            'activeOffers' => $activeOffers,
            'expiredOffers' => $expiredOffers,
            'recentApplications' => $recentApplications,
            'activeSubscription' => $activeSubscription
        ]);
    }

    #[Route('/job-offer/new', name: 'app_recruiter_job_offer_new')]
    public function newJobOffer(
        Request $request, 
        EntityManagerInterface $entityManager,
        SubscriptionService $subscriptionService
    ): Response {
        // Vérifier si l'utilisateur peut publier une nouvelle offre selon son abonnement
        if (!$subscriptionService->canPostJobOffer($this->getUser())) {
            $activeSubscription = $subscriptionService->getActiveSubscription($this->getUser());
            
            if (!$activeSubscription) {
                $this->addFlash('error', 'Vous devez avoir un abonnement actif pour publier des offres d\'emploi.');
                return $this->redirectToRoute('app_subscription_plans');
            } else {
                $this->addFlash('error', 'Vous avez atteint la limite de publication d\'offres pour votre abonnement actuel.');
                return $this->redirectToRoute('app_subscription_manage');
            }
        }
        
        $jobOffer = new JobOffer();
        $jobOffer->setRecruiter($this->getUser());
        
        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($jobOffer);
            $entityManager->flush();
            
            // Décrémenter le nombre d'offres restantes pour l'abonnement Basic
            $subscriptionService->decrementRemainingJobOffers($this->getUser());

            $this->addFlash('success', 'L\'offre d\'emploi a été créée avec succès.');
            return $this->redirectToRoute('app_recruiter_dashboard');
        }

        return $this->render('recruiter/job_offer_form.html.twig', [
            'form' => $form->createView(),
            'jobOffer' => $jobOffer
        ]);
    }

    #[Route('/job-offer/{id}/edit', name: 'app_recruiter_job_offer_edit')]
    public function editJobOffer(JobOffer $jobOffer, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette offre.');
        }

        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'offre d\'emploi a été mise à jour avec succès.');
            return $this->redirectToRoute('app_recruiter_dashboard');
        }

        return $this->render('recruiter/job_offer_form.html.twig', [
            'form' => $form->createView(),
            'jobOffer' => $jobOffer
        ]);
    }

    #[Route('/job-offer/{id}/toggle', name: 'app_recruiter_job_offer_toggle')]
    public function toggleJobOffer(JobOffer $jobOffer, EntityManagerInterface $entityManager): Response
    {
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette offre.');
        }

        $jobOffer->setIsActive(!$jobOffer->isActive());
        $entityManager->flush();

        $status = $jobOffer->isActive() ? 'activée' : 'désactivée';
        $this->addFlash('success', "L'offre d'emploi a été {$status} avec succès.");

        return $this->redirectToRoute('app_recruiter_dashboard');
    }

    #[Route('/applications', name: 'app_recruiter_applications')]
    public function applications(EntityManagerInterface $entityManager): Response
    {
        $applications = $entityManager->getRepository(JobOffer::class)
            ->findApplicationsByRecruiter($this->getUser());

        return $this->render('recruiter/applications.html.twig', [
            'applications' => $applications
        ]);
    }

    #[Route('/candidates', name: 'app_recruiter_candidates')]
    #[IsGranted('FULL_CV_ACCESS')]
    public function candidateSearch(
        Request $request,
        ApplicantRepository $applicantRepository
    ): Response {
        $query = $request->query->get('q');
        $skills = $request->query->all('skills');
        
        if ($query || !empty($skills)) {
            $candidates = $applicantRepository->searchBySkillsAndKeywords($skills, $query);
        } else {
            $candidates = $applicantRepository->findAll();
        }
        
        return $this->render('recruiter/candidates.html.twig', [
            'candidates' => $candidates,
            'query' => $query,
            'selectedSkills' => $skills
        ]);
    }
    
    #[Route('/candidates/{id}', name: 'app_recruiter_candidate_profile')]
    #[IsGranted('FULL_CV_ACCESS')]
    public function candidateProfile(Applicant $applicant): Response
    {
        return $this->render('recruiter/candidate_profile.html.twig', [
            'candidate' => $applicant
        ]);
    }
    
    #[Route('/advanced-search', name: 'app_recruiter_advanced_search')]
    #[IsGranted('ADVANCED_CANDIDATE_SEARCH')]
    public function advancedSearch(
        Request $request,
        ApplicantRepository $applicantRepository
    ): Response {
        $formData = $request->query->all();
        $results = [];
        
        if (!empty($formData)) {
            $results = $applicantRepository->advancedSearch(
                $formData['skills'] ?? [],
                $formData['education'] ?? null,
                $formData['experience'] ?? null,
                $formData['location'] ?? null
            );
        }
        
        return $this->render('recruiter/advanced_search.html.twig', [
            'results' => $results,
            'formData' => $formData
        ]);
    }
    
    #[Route('/recommendations', name: 'app_recruiter_recommendations')]
    #[IsGranted('AUTOMATIC_RECOMMENDATIONS')]
    public function recommendations(
        JobOfferRepository $jobOfferRepository,
        ApplicantRepository $applicantRepository
    ): Response {
        $recruiter = $this->getUser();
        $activeOffers = $jobOfferRepository->findBy(['recruiter' => $recruiter, 'isActive' => true]);
        
        $recommendations = [];
        foreach ($activeOffers as $offer) {
            $recommendations[$offer->getId()] = [
                'offer' => $offer,
                'candidates' => $applicantRepository->findMatchingCandidates($offer)
            ];
        }
        
        return $this->render('recruiter/recommendations.html.twig', [
            'recommendations' => $recommendations
        ]);
    }
    
    #[Route('/statistics', name: 'app_recruiter_statistics')]
    #[IsGranted('BASIC_STATISTICS')]
    public function statistics(
        StatisticsService $statisticsService,
        SubscriptionService $subscriptionService
    ): Response {
        $recruiter = $this->getUser();
        $hasDetailedStats = $subscriptionService->hasAccessToPremiumFeature($recruiter, 'detailed_statistics');
        
        // Statistiques de base
        $basicStats = $statisticsService->getBasicStatistics($recruiter);
        
        // Statistiques détaillées (uniquement pour Business)
        $detailedStats = null;
        if ($hasDetailedStats) {
            $detailedStats = $statisticsService->getDetailedStatistics($recruiter);
        }
        
        return $this->render('recruiter/statistics.html.twig', [
            'basicStats' => $basicStats,
            'detailedStats' => $detailedStats,
            'hasDetailedStats' => $hasDetailedStats
        ]);
    }
} 