<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/email-verification-sent', name: 'app_email_verification_sent')]
    public function emailVerificationSent(): Response
    {
        return $this->render('registration/email_verification_sent.html.twig');
    }

    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(string $token): Response
    {
        // TODO: Implémenter la vérification du token

        $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès.');
        return $this->redirectToRoute('app_login');
    }
}
