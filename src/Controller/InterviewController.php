<?php

namespace App\Controller;

use App\Entity\Interview;
use App\Entity\JobOffer;
use App\Form\InterviewType;
use App\Repository\InterviewRepository;
use App\Repository\JobOfferRepository;
use App\Service\VideoConferenceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/interviews')]
class InterviewController extends AbstractController
{
    private $videoConferenceService;
    private $interviewRepository;

    public function __construct(
        VideoConferenceService $videoConferenceService,
        InterviewRepository $interviewRepository
    ) {
        $this->videoConferenceService = $videoConferenceService;
        $this->interviewRepository = $interviewRepository;
    }

    /**
     * Affiche la liste des entretiens pour l'utilisateur connecté
     */
    #[Route('/', name: 'app_interviews_index')]
    public function index(): Response
    {
        $user = $this->getUser();

        $upcomingInterviews = $this->interviewRepository->findUpcomingInterviewsForUser($user);
        $pastInterviews = $this->interviewRepository->findPastInterviewsForUser($user);

        return $this->render('interview/index.html.twig', [
            'upcomingInterviews' => $upcomingInterviews,
            'pastInterviews' => $pastInterviews,
        ]);
    }

    /**
     * Crée un nouvel entretien
     */
    #[Route('/new', name: 'app_interview_new')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $interview = new Interview();
        $interview->setRecruiter($this->getUser());

        $form = $this->createForm(InterviewType::class, $interview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les conflits d'horaire
            $hasConflict = $this->interviewRepository->hasScheduleConflict(
                $this->getUser(),
                $interview->getScheduledAt(),
                (clone $interview->getScheduledAt())->modify('+1 hour')
            );

            if ($hasConflict) {
                $this->addFlash('error', 'Vous avez déjà un entretien programmé à cette heure.');
                return $this->redirectToRoute('app_interview_new');
            }

            // Vérifie aussi pour le candidat
            $hasApplicantConflict = $this->interviewRepository->hasScheduleConflict(
                $interview->getApplicant(),
                $interview->getScheduledAt(),
                (clone $interview->getScheduledAt())->modify('+1 hour')
            );

            if ($hasApplicantConflict) {
                $this->addFlash('error', 'Le candidat a déjà un entretien programmé à cette heure.');
                return $this->redirectToRoute('app_interview_new');
            }

            $entityManager->persist($interview);
            $entityManager->flush();

            // Création de la salle de visioconférence
            $this->videoConferenceService->createRoom($interview);

            $this->addFlash('success', 'L\'entretien a été planifié avec succès.');
            return $this->redirectToRoute('app_interviews_index');
        }

        return $this->render('interview/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Planifier un entretien pour une offre d'emploi spécifique
     */
    #[Route('/new/job/{id}', name: 'app_interview_new_job')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function newForJob(
        Request $request,
        JobOffer $jobOffer,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que l'offre appartient au recruteur connecté
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à planifier des entretiens pour cette offre.');
        }

        $interview = new Interview();
        $interview->setRecruiter($this->getUser());
        $interview->setJobOffer($jobOffer);
        $interview->setTitle('Entretien pour: ' . $jobOffer->getTitle());

        $form = $this->createForm(InterviewType::class, $interview, [
            'job_offer_id' => $jobOffer->getId(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les conflits d'horaire
            $hasConflict = $this->interviewRepository->hasScheduleConflict(
                $this->getUser(),
                $interview->getScheduledAt(),
                (clone $interview->getScheduledAt())->modify('+1 hour')
            );

            if ($hasConflict) {
                $this->addFlash('error', 'Vous avez déjà un entretien programmé à cette heure.');
                return $this->redirectToRoute('app_interview_new_job', ['id' => $jobOffer->getId()]);
            }

            // Vérifie aussi pour le candidat
            $hasApplicantConflict = $this->interviewRepository->hasScheduleConflict(
                $interview->getApplicant(),
                $interview->getScheduledAt(),
                (clone $interview->getScheduledAt())->modify('+1 hour')
            );

            if ($hasApplicantConflict) {
                $this->addFlash('error', 'Le candidat a déjà un entretien programmé à cette heure.');
                return $this->redirectToRoute('app_interview_new_job', ['id' => $jobOffer->getId()]);
            }

            $entityManager->persist($interview);
            $entityManager->flush();

            // Création de la salle de visioconférence
            $this->videoConferenceService->createRoom($interview);

            $this->addFlash('success', 'L\'entretien a été planifié avec succès.');
            return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        return $this->render('interview/new_for_job.html.twig', [
            'form' => $form->createView(),
            'jobOffer' => $jobOffer,
        ]);
    }

    /**
     * Afficher les détails d'un entretien
     */
    #[Route('/{id}', name: 'app_interview_show', methods: ['GET'])]
    public function show(Interview $interview): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est autorisé à voir cet entretien
        if (!$this->videoConferenceService->canAccessRoom($interview, $user)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cet entretien.');
        }

        // Vérifier si l'entretien est actif et rediriger directement vers la salle si c'est le cas
        $isActive = $this->videoConferenceService->isInterviewActive($interview);
        if ($isActive && !$interview->isCancelled()) {
            // Générer un token pour l'accès à la salle
            $token = $this->videoConferenceService->generateRoomToken($interview);

            // Rediriger vers la salle d'entretien
            return $this->redirectToRoute('app_interview_room', [
                'id' => $interview->getId(),
                'token' => $token
            ]);
        }

        return $this->render('interview/show.html.twig', [
            'interview' => $interview,
            'canJoin' => $isActive,
        ]);
    }

    /**
     * Rejoindre une salle de visioconférence
     */
    #[Route('/{id}/room', name: 'app_interview_room')]
    public function room(Interview $interview, Request $request): Response
    {
        $user = $this->getUser();
        $token = $request->query->get('token');

        // Vérifier que l'utilisateur est autorisé à accéder à cet entretien
        if (!$this->videoConferenceService->canAccessRoom($interview, $user)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette salle.');
        }

        // Vérifier la validité du token
        if (!$this->videoConferenceService->validateRoomToken($interview, $token)) {
            throw $this->createAccessDeniedException('Lien d\'accès invalide.');
        }

        // Vérifier que l'entretien n'est pas annulé
        if ($interview->isCancelled()) {
            $this->addFlash('error', 'Cet entretien a été annulé.');
            return $this->redirectToRoute('app_interviews_index');
        }

        // Vérifier que l'entretien est actif
        $now = new \DateTime();
        $scheduledTime = $interview->getScheduledAt();
        $earliestJoin = (clone $scheduledTime)->modify('-15 minutes');
        $latestJoin = (clone $scheduledTime)->modify('+1 hour');

        if ($now < $earliestJoin) {
            // L'entretien n'est pas encore disponible
            $this->addFlash('warning', 'Cet entretien sera disponible 15 minutes avant l\'heure prévue.');
            return $this->redirectToRoute('app_interview_show', ['id' => $interview->getId()]);
        } elseif ($now > $latestJoin) {
            // L'entretien est terminé
            $this->addFlash('warning', 'Cet entretien est terminé.');
            return $this->redirectToRoute('app_interview_show', ['id' => $interview->getId()]);
        }

        // Si l'entretien est actif mais son statut est toujours "scheduled", le mettre à "active"
        if ($interview->isScheduled()) {
            $interview->setStatus('active');
            $this->interviewRepository->save($interview, true);
        }

        // Obtenir la configuration pour le client
        $clientConfig = $this->videoConferenceService->getClientConfig($interview, $user);

        return $this->render('interview/room.html.twig', [
            'interview' => $interview,
            'clientConfig' => json_encode($clientConfig),
        ]);
    }

    /**
     * Annuler un entretien
     */
    #[Route('/{id}/cancel', name: 'app_interview_cancel', methods: ['POST'])]
    public function cancel(Interview $interview, Request $request): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est autorisé à annuler cet entretien
        if ($interview->getRecruiter() !== $user && $interview->getApplicant() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cet entretien.');
        }

        if ($this->isCsrfTokenValid('cancel' . $interview->getId(), $request->request->get('_token'))) {
            $this->videoConferenceService->cancelInterview($interview);

            $this->addFlash('success', 'L\'entretien a été annulé avec succès.');
        }

        return $this->redirectToRoute('app_interviews_index');
    }

    /**
     * Terminer un entretien
     */
    #[Route('/{id}/end', name: 'app_interview_end', methods: ['POST'])]
    public function end(Interview $interview, Request $request): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est autorisé à terminer cet entretien (uniquement le recruteur)
        if ($interview->getRecruiter() !== $user) {
            throw $this->createAccessDeniedException('Seul le recruteur peut terminer l\'entretien.');
        }

        if ($this->isCsrfTokenValid('end' . $interview->getId(), $request->request->get('_token'))) {
            $this->videoConferenceService->endInterview($interview);

            $this->addFlash('success', 'L\'entretien a été terminé avec succès.');
        }

        return $this->redirectToRoute('app_interviews_index');
    }

    /**
     * Liste des entretiens pour une offre d'emploi
     */
    #[Route('/job/{id}', name: 'app_interviews_for_job')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function interviewsForJob(JobOffer $jobOffer): Response
    {
        // Vérifier que l'offre appartient au recruteur connecté
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les entretiens pour cette offre.');
        }

        $interviews = $this->interviewRepository->findInterviewsForJobOffer($jobOffer->getId());

        return $this->render('interview/for_job.html.twig', [
            'jobOffer' => $jobOffer,
            'interviews' => $interviews,
        ]);
    }

    /**
     * Rejoindre directement une salle de visioconférence (accès direct)
     */
    #[Route('/{id}/direct-join', name: 'app_interview_direct_join')]
    public function directJoin(Interview $interview): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est autorisé à accéder à cet entretien
        if (!$this->videoConferenceService->canAccessRoom($interview, $user)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette salle.');
        }

        // Vérifier que l'entretien n'est pas annulé
        if ($interview->isCancelled()) {
            $this->addFlash('error', 'Cet entretien a été annulé.');
            return $this->redirectToRoute('app_interviews_index');
        }

        // Générer un token pour l'accès à la salle
        $token = $this->videoConferenceService->generateRoomToken($interview);

        // Rediriger vers la salle d'entretien
        return $this->redirectToRoute('app_interview_room', [
            'id' => $interview->getId(),
            'token' => $token
        ]);
    }
}
