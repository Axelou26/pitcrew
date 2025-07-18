<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\Cache;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    #[Cache(maxage: 0, public: false, mustRevalidate: true)]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
        // Optimisation: vérification rapide sans accès à la base de données
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Optimisation: éviter les accès superflus à la session
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Optimisation: compression gzip pour les réponses HTML
        $response = $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);

        // Optimiser les en-têtes de cache pour la page de login
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall
            .');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('security/forgot_password.html.twig');
    }
}
