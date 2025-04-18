<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

        return $this->redirectToRoute('app_email_verification_sent');
    }
}
