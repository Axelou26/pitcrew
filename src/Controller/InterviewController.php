<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Interview;
use App\Entity\JobOffer;
use App\Form\InterviewType;
use App\Repository\InterviewRepository;
use App\Service\VideoConferenceService;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/interviews')]
class InterviewController extends AbstractController
{
    private $videoConfService;
    private $interviewRepository;

    public function __construct(
        VideoConferenceService $videoConfService,
        InterviewRepository $interviewRepository
    ) {
        $this->videoConfService    = $videoConfService;
        $this->interviewRepository = $interviewRepository;
    }

    /**
     * Affiche la liste des entretiens pour l'utilisateur connecté.
     */
    #[Route('/', name: 'app_interviews_index')]
    public function index(): Response
    {
        $user = $this->getUser();

        $upcomingInterviews = $this->interviewRepository->findUpcomingInterviewsForUser($user);
        $pastInterviews     = $this->interviewRepository->findPastInterviewsForUser($user);

        return $this->render('interview/index.html.twig', [
            'upcomingInterviews' => $upcomingInterviews,
            'pastInterviews'     => $pastInterviews,
        ]);
    }

    /**
     * Crée un nouvel entretien.
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
            // Ensure JobOffer is set
            if (!$interview->getJobOffer()) {
                $this->addFlash('error', 'Vous devez sélectionner une offre d\'emploi.');

                return $this->render('interview/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Vérifier les conflits d'horaire
            $conflictResponse = $this->checkScheduleConflicts($interview, 'app_interview_new');
            if ($conflictResponse) {
                return $conflictResponse;
            }

            $entityManager->persist($interview);
            $entityManager->flush();

            // Création de la salle de visioconférence
            $this->videoConfService->createRoom($interview);

            $this->addFlash('success', 'L\'entretien a été planifié avec succès.');

            return $this->redirectToRoute('app_interviews_index');
        }

        return $this->render('interview/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Planifier un entretien pour une offre d'emploi spécifique.
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
            throw $this
                ->createAccessDeniedException('Vous n\'êtes pas autorisé à planifier des entretiens pour cette offre.');
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
            $conflictResponse = $this->checkScheduleConflicts(
                $interview,
                'app_interview_new_job',
                ['id' => $jobOffer->getId()]
            );
            if ($conflictResponse) {
                return $conflictResponse;
            }

            $entityManager->persist($interview);
            $entityManager->flush();

            // Création de la salle de visioconférence
            $this->videoConfService->createRoom($interview);

            $this->addFlash('success', 'L\'entretien a été planifié avec succès.');

            return $this->redirectToRoute('app_interviews_for_job', ['id' => $jobOffer->getId()]);
        }

        return $this->render('interview/new_for_job.html.twig', [
            'form'     => $form->createView(),
            'jobOffer' => $jobOffer,
        ]);
    }

    /**
     * Afficher les détails d'un entretien.
     */
    #[Route('/{id}', name: 'app_interview_show', methods: ['GET'])]
    public function show(Interview $interview): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est autorisé à voir cet entretien
        if (!$this->videoConfService->canAccessRoom($interview, $user)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cet entretien.');
        }

        // Vérifier si l'entretien est actif et rediriger directement vers la salle si c'est le cas
        $isActive = $this->videoConfService->isInterviewActive($interview);
        if ($isActive && !$interview->isCancelled()) {
            // Générer un token pour l'accès à la salle
            $token = $this->videoConfService->generateRoomToken($interview);

            // Rediriger vers la salle d'entretien
            return $this->redirectToRoute('app_interview_room', [
                'id'    => $interview->getId(),
                'token' => $token,
            ]);
        }

        return $this->render('interview/show.html.twig', [
            'interview' => $interview,
            'canJoin'   => $isActive,
        ]);
    }

    /**
     * Rejoindre une salle de visioconférence.
     */
    #[Route('/{id}/room', name: 'app_interview_room')]
    public function room(Interview $interview, Request $request): Response
    {
        $user  = $this->getUser();
        $token = $request->query->get('token');

        // Vérifier que l'utilisateur est autorisé à accéder à cet entretien
        if (!$this->videoConfService->canAccessRoom($interview, $user)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette salle.');
        }

        // Vérifier la validité du token
        if (!$this->videoConfService->validateRoomToken($interview, $token)) {
            throw $this->createAccessDeniedException('Lien d\'accès invalide.');
        }

        // Vérifier que l'entretien n'est pas annulé
        if ($interview->isCancelled()) {
            $this->addFlash('error', 'Cet entretien a été annulé.');

            return $this->redirectToRoute('app_interviews_index');
        }

        // Vérifier que l'entretien est actif
        $now           = new DateTimeImmutable();
        $scheduledTime = $interview->getScheduledAt();
        $earliestJoin  = (clone $scheduledTime)->modify('-15 minutes');
        $latestJoin    = (clone $scheduledTime)->modify('+1 hour');

        if ($now < $earliestJoin) {
            // L'entretien n'est pas encore disponible
            $this->addFlash('warning', 'Cet entretien sera disponible 15 minutes avant l\'heure prévue.');

            return $this->redirectToRoute('app_interview_show', ['id' => $interview->getId()]);
        }
        if ($now > $latestJoin) {
            // L'entretien est terminé
            $this->addFlash('warning', 'Cet entretien est terminé.');

            return $this->redirectToRoute('app_interview_show', ['id' => $interview->getId()]);
        }

        // Si l'entretien est actif mais son statut est toujours "scheduled", le mettre à "active"
        if ($interview->isScheduled()) {
            $interview->setStatus('active');
            $this->interviewRepository->save($interview, true);
        }

        // Obtenir la configuration pour le client avec paramètres d'accès direct
        $clientConfig               = $this->videoConfService->getClientConfig($interview, $user);
        $clientConfig['directJoin'] = true;

        // Rendu de la vue avec la salle d'entretien
        return $this->render('interview/room.html.twig', [
            'interview'    => $interview,
            'clientConfig' => json_encode($clientConfig),
        ]);
    }

    /**
     * Annuler un entretien.
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
            $this->videoConfService->cancelInterview($interview);

            $this->addFlash('success', 'L\'entretien a été annulé avec succès.');
        }

        return $this->redirectToRoute('app_interviews_index');
    }

    /**
     * Terminer un entretien.
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
            $this->videoConfService->endInterview($interview);

            $this->addFlash('success', 'L\'entretien a été terminé avec succès.');
        }

        return $this->redirectToRoute('app_interviews_index');
    }

    /**
     * Liste des entretiens pour une offre d'emploi.
     */
    #[Route('/job/{id}', name: 'app_interviews_for_job')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function interviewsForJob(JobOffer $jobOffer): Response
    {
        // Vérifier que l'offre appartient au recruteur connecté
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this
                ->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les entretiens pour cette offre.');
        }

        $interviews = $this->interviewRepository->findInterviewsForJobOffer($jobOffer);

        return $this->render('interview/for_job.html.twig', [
            'jobOffer'   => $jobOffer,
            'interviews' => $interviews,
        ]);
    }

    /**
     * Rejoindre directement une salle de visioconférence (accès direct).
     */
    #[Route('/{id}/direct-join', name: 'app_interview_direct_join')]
    public function directJoin(Interview $interview): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est autorisé à accéder à cet entretien
        if (!$this->videoConfService->canAccessRoom($interview, $user)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette salle.');
        }

        // Générer un token et rediriger vers la salle normale
        $token = $this->videoConfService->generateRoomToken($interview);

        return $this->redirectToRoute('app_interview_room', [
            'id'    => $interview->getId(),
            'token' => $token,
        ]);
    }

    /**
     * Fin d'appel vidéo et redirection.
     */
    #[Route('/{id}/end-call', name: 'app_interview_end_call')]
    public function endCall(Interview $interview): Response
    {
        // Vérifier que l'utilisateur est autorisé à accéder à cet entretien
        if (!$this->videoConfService->canAccessRoom($interview, $this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cet entretien.');
        }

        // Si l'entretien est actif, mettre à jour son statut
        if ($interview->isActive() && !$interview->isCompleted()) {
            $this->addFlash('success', 'L\'appel vidéo a été terminé.');
        }

        // Rediriger vers la page de détails
        return $this->redirectToRoute('app_interview_show', [
            'id' => $interview->getId(),
        ]);
    }

    /**
     * Vérifier les conflits d'horaire pour un entretien.
     */
    private function checkScheduleConflicts(
        Interview $interview,
        string $redirectRoute,
        array $routeParams = []
    ): ?Response {
        // Vérifier les conflits d'horaire pour le recruteur
        $scheduledAt = $interview->getScheduledAt();
        if ($scheduledAt === null) {
            $this->addFlash('error', 'La date de l\'entretien n\'est pas définie.');

            return $this->redirectToRoute($redirectRoute, $routeParams);
        }

        // Convertir en DateTimeImmutable pour correspondre à la signature de hasScheduleConflict
        $startTimeImmutable = \DateTimeImmutable::createFromInterface($scheduledAt);
        $endTimeImmutable = $startTimeImmutable->modify('+1 hour');

        $hasConflict = $this->interviewRepository->hasScheduleConflict(
            $this->getUser(),
            $startTimeImmutable,
            $endTimeImmutable
        );

        if ($hasConflict) {
            $this->addFlash('error', 'Vous avez déjà un entretien programmé à cette heure.');

            return $this->redirectToRoute($redirectRoute, $routeParams);
        }

        // Vérifier les conflits d'horaire pour le candidat
        $applicant = $interview->getApplicant();
        if ($applicant === null) {
            $this->addFlash('error', 'Aucun candidat n\'est sélectionné pour cet entretien.');

            return $this->redirectToRoute($redirectRoute, $routeParams);
        }

        // Convertir en DateTimeImmutable pour correspondre à la signature de hasScheduleConflict
        $startTimeImmutable = \DateTimeImmutable::createFromInterface($scheduledAt);
        $endTimeImmutable = $startTimeImmutable->modify('+1 hour');

        $hasApplicantConflict = $this->interviewRepository->hasScheduleConflict(
            $applicant,
            $startTimeImmutable,
            $endTimeImmutable
        );

        if ($hasApplicantConflict) {
            $this->addFlash('error', 'Le candidat a déjà un entretien programmé à cette heure.');

            return $this->redirectToRoute($redirectRoute, $routeParams);
        }

        return null;
    }

    private function getAvailableSlots(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $durationMinutes = 60
    ): array {
        $slots       = [];
        $currentDate = $startDate instanceof \DateTimeImmutable
            ? clone $startDate
            : \DateTimeImmutable::createFromInterface($startDate);

        $endDateTime = $endDate instanceof \DateTimeImmutable
            ? clone $endDate
            : \DateTimeImmutable::createFromInterface($endDate);

        while ($currentDate < $endDateTime) {
            $slots[] = clone $currentDate;
            $currentDate = $currentDate->modify("+{$durationMinutes} minutes");
        }

        return $slots;
    }

    private function isValidDateTime(string $dateString): bool
    {
        // Format de date attendu
        $format = 'Y-m-d H:i';

        // Tenter de créer une date à partir de la chaîne
        $date = \DateTimeImmutable::createFromFormat($format, $dateString);

        // Vérifier la validité
        $isFormatValid = $date !== false;
        $isValueValid  = $isFormatValid && $date->format($format) === $dateString;

        return $isValueValid;
    }

    private function validateInterviewData(Request $request, ?Interview $interview = null): array
    {
        $errors = [];
        $data   = json_decode($request->getContent(), true);

        if (!$data) {
            $errors[] = 'Données invalides';

            return $errors;
        }

        // Validation de base
        if (empty($data['title'])) {
            $errors[] = 'Le titre est obligatoire';
        }

        if (empty($data['scheduledAt'])) {
            $errors[] = 'La date est obligatoire';
        } elseif (!$this->isValidDateTime($data['scheduledAt'])) {
            $errors[] = 'Format de date invalide';
        }

        return $errors;
    }
}
