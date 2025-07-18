<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class EmailVerificationController extends AbstractController
{
    public function __construct(
        private EmailService $emailService,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/email-verification-sent', name: 'app_email_verification_sent')]
    public function emailVerificationSent(): Response
    {
        return $this->render('registration/email_verification_sent.html.twig');
    }

    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(string $token): Response
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Token de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null); // On supprime le token après utilisation
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification-email', name: 'app_resend_verification_email')]
    public function resendVerificationEmail(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre adresse email est déjà vérifiée.');
            return $this->redirectToRoute('app_home');
        }

        $this->emailService->sendRegistrationConfirmation($user);
        $this->addFlash('success', 'Un nouvel email de vérification vous a été envoyé.');

        // Ajouter des informations supplémentaires en mode développement
        if ($this->getParameter('kernel.environment') === 'dev') {
            $mailerDsn = $_SERVER['MAILER_DSN'] ?? 'non configuré';
            $message = 'Mode développement : configuration SMTP = ' . $mailerDsn;
            $this->addFlash('info', $message);

            if (strpos($mailerDsn, 'localhost:1025') !== false) {
                $this->addFlash('info', 'Pour voir les emails: ' .
                    '<a href="http://localhost:8025" target="_blank">MailHog</a>');
            }
        }

        return $this->redirectToRoute('app_email_verification_sent');
    }

    #[Route('/debug-email-config', name: 'app_debug_email_config')]
    public function debugEmailConfig(): Response
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException('Cette page n\'est disponible qu\'en environnement de développement');
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        $mailerDsn = $_SERVER['MAILER_DSN'] ?? 'non configuré';
        $emailConfig = [
            'MAILER_DSN' => $mailerDsn,
            'Environnement' => $this->getParameter('kernel.environment'),
            'Email utilisateur' => $user->getEmail(),
            'Statut de vérification' => $user->isVerified() ? 'Vérifié' : 'Non vérifié',
            'Token de vérification' => $user->getVerificationToken() ?? 'Aucun'
        ];

        return $this->render('emails/debug.html.twig', [
            'emailConfig' => $emailConfig
        ]);
    }

    #[Route('/debug-send-test-email', name: 'app_debug_send_test_email', methods: ['POST'])]
    public function sendTestEmail(Request $request): Response
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException('Cette page n\'est disponible qu\'en environnement de développement');
        }

        $email = $request->request->get('test_email');

        if (!$email) {
            $this->addFlash('error', 'Adresse email requise');
            return $this->redirectToRoute('app_debug_email_config');
        }

        try {
            $this->emailService->sendTestEmail($email);
            $this->addFlash('success', 'Email de test envoyé à ' . $email);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_debug_email_config');
    }
}
