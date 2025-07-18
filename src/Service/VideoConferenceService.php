<?php

namespace App\Service;

use App\Entity\Interview;
use App\Entity\User;
use App\Repository\InterviewRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use DateTime;

class VideoConferenceService
{
    private $interviewRepository;
    private $urlGenerator;
    private $apiKey;
    private $jitsiDomain;

    public function __construct(
        InterviewRepository $interviewRepository,
        UrlGeneratorInterface $urlGenerator,
        string $apiKey = 'pitcrew_secure_key', // À configurer dans .env ou parameters.yaml
        string $jitsiDomain = 'meet.jit.si'
    ) {
        $this->interviewRepository = $interviewRepository;
        $this->urlGenerator = $urlGenerator;
        $this->apiKey = $apiKey;
        $this->jitsiDomain = $jitsiDomain;
    }

    /**
     * Crée une nouvelle salle de visioconférence pour un entretien
     */
    public function createRoom(Interview $interview): string
    {
        // Génération d'un identifiant unique pour la salle
        $roomId = 'pitcrew_' . $interview->getId() . '_' . bin2hex(random_bytes(8));

        // Stockage de l'ID de la salle
        $interview->setRoomId($roomId);

        // Génération de l'URL de la salle
        $token = $this->generateRoomToken($interview);
        $meetingUrl = $this->urlGenerator->generate('app_interview_room', [
            'id' => $interview->getId(),
            'token' => $token
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $interview->setMeetingUrl($meetingUrl);
        $this->interviewRepository->save($interview, true);

        return $roomId;
    }

    /**
     * Vérifie si un utilisateur a le droit d'accéder à une salle
     */
    public function canAccessRoom(Interview $interview, User $user): bool
    {
        // Vérifie si l'utilisateur est soit le recruteur soit le candidat pour cet entretien
        return $interview->getRecruiter() === $user || $interview->getApplicant() === $user;
    }

    /**
     * Génère un jeton d'accès pour la salle
     */
    public function generateRoomToken(Interview $interview): string
    {
        // Utilisation d'un hash sécurisé avec sel
        return hash_hmac('sha256', $interview->getId() . $interview->getRoomId(), $this->apiKey);
    }

    /**
     * Vérifie la validité d'un jeton d'accès
     */
    public function validateRoomToken(Interview $interview, string $token): bool
    {
        $expectedToken = $this->generateRoomToken($interview);
        return hash_equals($expectedToken, $token);
    }

    /**
     * Vérifie si l'entretien est actif (peut être rejoint)
     */
    public function isInterviewActive(Interview $interview): bool
    {
        $now = new DateTime();
        $scheduledTime = $interview->getScheduledAt();

        // Entretien actif si on est dans la fenêtre de 15 minutes avant à 1 heure après
        $earliestJoin = (clone $scheduledTime)->modify('-15 minutes');
        $latestJoin = (clone $scheduledTime)->modify('+1 hour');

        // On vérifie seulement la fenêtre de temps et que l'entretien n'est pas annulé
        return $now >= $earliestJoin && $now <= $latestJoin && !$interview->isCancelled();
    }

    /**
     * Termine une session d'entretien
     */
    public function endInterview(Interview $interview): void
    {
        $interview->setEndedAt(new DateTime());
        $interview->setStatus('completed');
        $this->interviewRepository->save($interview, true);
    }

    /**
     * Annule un entretien planifié
     */
    public function cancelInterview(Interview $interview): void
    {
        $interview->setStatus('cancelled');
        $this->interviewRepository->save($interview, true);
    }

    /**
     * Génère les données de configuration côté client pour la visioconférence
     */
    public function getClientConfig(Interview $interview, User $user): array
    {
        $isRecruiter = $interview->getRecruiter() === $user;

        return [
            'roomName' => $interview->getRoomId(),
            'userDisplayName' => $user->getFullName(),
            'subject' => $interview->getTitle(),
            'userRole' => $isRecruiter ? 'host' : 'participant',
            'userEmail' => $user->getEmail(),
            'jitsiDomain' => $this->jitsiDomain,
            // Configuration supplémentaire pour éviter les problèmes d'accès
            'enableLobby' => false,
            'openBridgeChannel' => 'websocket',
            'startWithVideoMuted' => false,
            'startWithAudioMuted' => false,
            'enableWelcomePage' => false,
            'prejoinPageEnabled' => false,
            'membersOnly' => false
        ];
    }

    /**
     * Obtient l'URL directe de Jitsi Meet pour l'interface externe
     */
    public function getDirectJitsiUrl(Interview $interview): string
    {
        return 'https://' . $this->jitsiDomain . '/' . $interview->getRoomId();
    }
}
