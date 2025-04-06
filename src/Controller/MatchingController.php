<?php

namespace App\Controller;

use App\Entity\Applicant;
use App\Entity\JobOffer;
use App\Repository\UserRepository;
use App\Repository\JobOfferRepository;
use App\Repository\ApplicantRepository;
use App\Service\MatchingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/matching')]
class MatchingController extends AbstractController
{
    private MatchingService $matchingService;
    private UserRepository $userRepository;
    private JobOfferRepository $jobOfferRepository;
    private ApplicantRepository $applicantRepository;

    public function __construct(
        MatchingService $matchingService,
        UserRepository $userRepository,
        JobOfferRepository $jobOfferRepository,
        ApplicantRepository $applicantRepository
    ) {
        $this->matchingService = $matchingService;
        $this->userRepository = $userRepository;
        $this->jobOfferRepository = $jobOfferRepository;
        $this->applicantRepository = $applicantRepository;
    }

    /**
     * Affiche les suggestions d'offres d'emploi pour un candidat
     */
    #[Route('/suggestions/candidate', name: 'app_matching_suggestions_candidate')]
    #[IsGranted('ROLE_POSTULANT')]
    public function suggestJobOffersForCandidate(Request $request): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        // Nombre de suggestions à afficher
        $limit = $request->query->getInt('limit', 10);
        
        try {
            // Récupérer les meilleures offres pour ce candidat
            $suggestedOffers = $this->matchingService->findBestJobOffersForCandidate($user, $limit);
            
            return $this->render('matching/candidate_suggestions.html.twig', [
                'suggestedOffers' => $suggestedOffers
            ]);
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_dashboard');
        }
    }

    /**
     * Affiche les suggestions de candidats pour une offre d'emploi
     */
    #[Route('/suggestions/job-offer/{id}', name: 'app_matching_suggestions_job_offer')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function suggestCandidatesForJobOffer(Request $request, int $id): Response
    {
        // Récupérer manuellement l'offre d'emploi
        $jobOffer = $this->jobOfferRepository->find($id);
        
        // Vérifier que l'offre existe
        if (!$jobOffer) {
            $this->addFlash('error', 'L\'offre d\'emploi demandée n\'existe pas.');
            return $this->redirectToRoute('app_recruiter_dashboard');
        }
        
        // Vérifier que l'offre appartient bien au recruteur connecté
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette page.');
        }
        
        // Nombre de suggestions à afficher
        $limit = $request->query->getInt('limit', 15);
        
        // Récupérer les meilleurs candidats pour cette offre
        $suggestedCandidates = $this->matchingService->findBestCandidatesForJobOffer($jobOffer, $limit);
        
        return $this->render('matching/job_offer_suggestions.html.twig', [
            'suggestedCandidates' => $suggestedCandidates,
            'jobOffer' => $jobOffer
        ]);
    }

    /**
     * Calcule et retourne le score de compatibilité entre un candidat et une offre d'emploi (API)
     */
    #[Route('/api/compatibility-score', name: 'app_matching_api_compatibility_score', methods: ['POST'])]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function getCompatibilityScore(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['applicantId']) || !isset($data['jobOfferId'])) {
            return new JsonResponse(['error' => 'Paramètres manquants'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $applicant = $this->matchingService->getApplicantById($data['applicantId']);
            $jobOffer = $this->jobOfferRepository->find($data['jobOfferId']);
            
            if (!$jobOffer) {
                return new JsonResponse(['error' => 'Offre non trouvée'], Response::HTTP_NOT_FOUND);
            }
            
            // Vérifier que l'offre appartient bien au recruteur connecté
            if ($jobOffer->getRecruiter() !== $this->getUser()) {
                return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
            }
            
            $compatibilityScore = $this->matchingService->calculateCompatibilityScore($applicant, $jobOffer);
            
            return new JsonResponse($compatibilityScore);
        } catch (\LogicException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Affiche le tableau de bord de matching pour les recruteurs
     */
    #[Route('/dashboard/recruiter', name: 'app_matching_dashboard_recruiter')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function recruiterDashboard(): Response
    {
        // Récupérer les offres du recruteur
        $jobOffers = $this->jobOfferRepository->findBy(['recruiter' => $this->getUser()]);
        
        // Pour chaque offre, pré-calculer les 5 meilleurs candidats
        $offerSuggestions = [];
        foreach ($jobOffers as $jobOffer) {
            $offerSuggestions[$jobOffer->getId()] = $this->matchingService->findBestCandidatesForJobOffer($jobOffer, 5);
        }
        
        return $this->render('matching/recruiter_dashboard.html.twig', [
            'jobOffers' => $jobOffers,
            'offerSuggestions' => $offerSuggestions
        ]);
    }

    /**
     * Affiche le tableau de bord de matching pour les candidats
     */
    #[Route('/dashboard/candidate', name: 'app_matching_dashboard_candidate')]
    #[IsGranted('ROLE_POSTULANT')]
    public function candidateDashboard(): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        try {
            // Récupérer les meilleures offres pour ce candidat
            $suggestedOffers = $this->matchingService->findBestJobOffersForCandidate($user, 10);
            
            return $this->render('matching/candidate_dashboard.html.twig', [
                'suggestedOffers' => $suggestedOffers
            ]);
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_dashboard');
        }
    }

    /**
     * Affiche les détails de matching pour un candidat spécifique
     */
    #[Route('/candidate/{id}/matching-details', name: 'app_matching_candidate_details')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function candidateMatchingDetails(Applicant $applicant, Request $request): Response
    {
        $jobOfferId = $request->query->getInt('job_offer');
        $jobOffer = null;
        $compatibilityScore = null;
        
        // Si une offre d'emploi spécifique est demandée, calculer le score pour cette offre
        if ($jobOfferId) {
            $jobOffer = $this->jobOfferRepository->find($jobOfferId);
            
            if ($jobOffer) {
                $compatibilityScore = $this->matchingService->calculateCompatibilityScore($applicant, $jobOffer);
            }
        }
        
        // Trouver les meilleures offres pour ce candidat
        $bestOffers = $this->matchingService->findBestJobOffersForCandidate($applicant, 5);
        
        return $this->render('matching/candidate_matching_details.html.twig', [
            'applicant' => $applicant,
            'jobOffer' => $jobOffer,
            'compatibilityScore' => $compatibilityScore,
            'bestOffers' => $bestOffers
        ]);
    }
} 