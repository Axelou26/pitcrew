<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Recruiter;
use App\Entity\Subscription;
use App\Form\RegistrationFormType;
use App\Form\UserTypeFormType;
use App\Service\SubscriptionService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\EmailService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    private $stripeService;
    private $params;
    private $urlGenerator;
    private $entityManager;

    public function __construct(
        StripeService $stripeService,
        ParameterBagInterface $params,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->stripeService = $stripeService;
        $this->params = $params;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;

        // Vérifier si la bibliothèque Stripe est disponible
        try {
            if (class_exists('\Stripe\Stripe')) {
                \Stripe\Stripe::setAppInfo(
                    'PitCrew Subscription',
                    '1.0.0',
                    'https://pitcrew.fr'
                );
            }
        } catch (\Exception $e) {
            // Ne rien faire, la vérification sera faite lorsque nécessaire
        }
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, SessionInterface $session): Response
    {
        // Créer le formulaire pour le choix du type d'utilisateur
        $form = $this->createForm(UserTypeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Stocker le type d'utilisateur en session
            $userType = $form->get('userType')->getData();
            $session->set('registration_user_type', $userType);

            // Rediriger vers le formulaire d'inscription complet
            return $this->redirectToRoute('app_register_details');
        }

        return $this->render('registration/user_type.html.twig', [
            'userTypeForm' => $form->createView(),
        ]);
    }

    #[Route('/register/details', name: 'app_register_details')]
    public function registerDetails(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        SubscriptionService $subscriptionService
    ): Response {
        // Récupérer le type d'utilisateur depuis la session
        $userType = $session->get('registration_user_type');

        // Rediriger vers la première étape si le type n'est pas défini
        if (!$userType) {
            return $this->redirectToRoute('app_register');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'user_type' => $userType,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Définir les rôles - un seul rôle par utilisateur
                $user->setRoles([$userType]);

                // Encoder le mot de passe
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Pour les recruteurs, rediriger toujours vers le choix d'abonnement
                if ($userType === User::ROLE_RECRUTEUR) {
                    // Enregistrer l'utilisateur
                    $entityManager->persist($user);
                    $entityManager->flush();

                    // Stocker l'ID de l'utilisateur en session pour continuer le processus
                    $session->set('registration_user_id', $user->getId());

                    // Rediriger vers la page de choix d'abonnement
                    return $this->redirectToRoute('app_register_subscription');
                } elseif ($userType === User::ROLE_POSTULANT) {
                    // Traitement pour les postulants
                    $entityManager->persist($user);
                    $entityManager->flush();

                    // Nettoyer la session
                    $session->remove('registration_user_type');

                    return $this->redirectToRoute('app_email_verification_sent');
                }
            }

            // En cas d'erreur de validation, renvoyer une réponse 422
            return $this->render('registration/register_details.html.twig', [
                'registrationForm' => $form->createView(),
                'userType' => $userType,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->render('registration/register_details.html.twig', [
            'registrationForm' => $form->createView(),
            'userType' => $userType,
        ]);
    }

    private function createRegistrationCheckoutSession(User $user, Subscription $subscription)
    {
        try {
            // Initialiser Stripe
            \Stripe\Stripe::setApiKey($this->params->get('stripe_secret_key'));

            // Créer ou récupérer le client Stripe
            $stripeCustomerId = $user->getStripeCustomerId();

            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->getEmail(),
                    'name' => $user->getFullName(),
                    'metadata' => [
                        'user_id' => $user->getId()
                    ]
                ]);

                $stripeCustomerId = $customer->id;
                $user->setStripeCustomerId($stripeCustomerId);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            // Créer la session de paiement avec les URLs spécifiques à l'inscription
            $successUrl = $this->urlGenerator->generate('app_register_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->urlGenerator->generate('app_register_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Déterminer le prix en centimes
            $priceInCents = (int)($subscription->getPrice() * 100);

            $sessionParams = [
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Abonnement ' . $subscription->getName(),
                            'description' => 'Abonnement ' . $subscription->getName() . ' pour ' . $subscription->getDuration() . ' jours',
                            'metadata' => [
                                'subscription_id' => $subscription->getId()
                            ]
                        ],
                        'unit_amount' => $priceInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId()
                ]
            ];

            $session = \Stripe\Checkout\Session::create($sessionParams);
            return $session;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    #[Route('/register/subscription', name: 'app_register_subscription')]
    #[Route('/subscription/register-subscription', name: 'app_register_subscription_alias')]
    public function registerSubscription(
        Request $request,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        SubscriptionService $subscriptionService,
        StripeService $stripeService
    ): Response {
        // Récupérer l'ID de l'utilisateur depuis la session
        $userId = $session->get('registration_user_id');
        if (!$userId) {
            $this->addFlash('error', 'Aucun utilisateur trouvé en session.');
            return $this->redirectToRoute('app_register');
        }

        // Récupérer l'utilisateur
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_register');
        }

        // Vérifier que l'utilisateur est bien un recruteur
        if (!in_array('ROLE_RECRUTEUR', $user->getRoles())) {
            $this->addFlash('error', 'Vous devez être recruteur pour accéder à cette page.');
            return $this->redirectToRoute('app_register');
        }

        // Récupérer tous les abonnements actifs
        $subscriptions = $entityManager->getRepository('App\Entity\Subscription')->findBy(
            ['isActive' => true],
            ['price' => 'ASC']
        );

        if (empty($subscriptions)) {
            $this->addFlash('error', 'Aucun abonnement actif trouvé.');
            return $this->render('registration/select_subscription.html.twig', [
                'subscriptions' => [],
                'stripe_public_key' => $this->params->get('stripe_public_key'),
                'is_test_mode' => $stripeService->isTestMode(),
                'is_offline_mode' => $stripeService->isOfflineMode()
            ]);
        }

        // Rendre obligatoire le choix d'un abonnement
        if ($request->isMethod('POST')) {
            $subscriptionId = $request->request->get('subscription_id');

            if (!$subscriptionId) {
                $this->addFlash('error', 'Vous devez choisir un abonnement pour finaliser votre inscription.');
                return $this->render('registration/select_subscription.html.twig', [
                    'subscriptions' => $subscriptions,
                    'stripe_public_key' => $this->params->get('stripe_public_key'),
                    'is_test_mode' => $stripeService->isTestMode(),
                    'is_offline_mode' => $stripeService->isOfflineMode()
                ]);
            }

            $subscription = $entityManager->getRepository('App\Entity\Subscription')->find($subscriptionId);

            if (!$subscription) {
                $this->addFlash('error', 'L\'abonnement sélectionné est invalide.');
                return $this->render('registration/select_subscription.html.twig', [
                    'subscriptions' => $subscriptions,
                    'stripe_public_key' => $this->params->get('stripe_public_key'),
                    'is_test_mode' => $stripeService->isTestMode(),
                    'is_offline_mode' => $stripeService->isOfflineMode()
                ]);
            }

            // Option pour le mode test: bypass Stripe pour les tests
            $useTestMode = $request->query->has('test_mode') && $stripeService->isTestMode();

            // Si c'est un abonnement gratuit, en mode hors ligne ou useTestMode, l'activer directement
            if ($subscription->getPrice() == 0 || $useTestMode || $stripeService->isOfflineMode()) {
                $recruiterSubscription = $subscriptionService->createSubscription($user, $subscription);

                // Si c'est un test ou mode hors ligne, marquer comme tel
                if ($useTestMode || $stripeService->isOfflineMode()) {
                    $recruiterSubscription->setPaymentStatus('test_mode');
                    $entityManager->flush();
                    $session->remove('registration_user_id');
                    $session->remove('registration_user_type');
                    $this->addFlash('success', 'Votre compte et votre abonnement ont été activés en mode test !');
                } else {
                    $session->remove('registration_user_id');
                    $session->remove('registration_user_type');
                    $this->addFlash('success', 'Votre compte et votre abonnement gratuit ont été activés avec succès !');
                }

                return $this->redirectToRoute('app_login');
            }

            // Pour les abonnements payants, créer une session Stripe et rediriger
            try {
                $session->set('pending_subscription_id', $subscription->getId());

                if ($stripeService->isOfflineMode()) {
                    // En mode hors ligne, simuler le paiement
                    return $this->redirectToRoute('app_register_subscription_success');
                } else {
                    // Mode normal avec Stripe
                    $stripeSession = $this->createRegistrationCheckoutSession($user, $subscription);
                    return $this->redirect($stripeSession->url);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la session de paiement : ' . $e->getMessage());

                // Si une erreur survient, essayer en mode hors ligne
                if (!$stripeService->isOfflineMode()) {
                    return $this->redirectToRoute('app_register_subscription_success');
                }
            }
        }

        return $this->render('registration/select_subscription.html.twig', [
            'subscriptions' => $subscriptions,
            'stripe_public_key' => $this->params->get('stripe_public_key'),
            'is_test_mode' => $stripeService->isTestMode(),
            'is_offline_mode' => $stripeService->isOfflineMode(),
            'required' => true // Indiquer que le choix de l'abonnement est obligatoire
        ]);
    }

    #[Route('/register/subscription/success', name: 'app_register_subscription_success')]
    public function subscriptionSuccess(
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        SubscriptionService $subscriptionService,
        EmailService $emailService
    ): Response {
        // Récupérer l'ID de l'utilisateur depuis la session
        $userId = $session->get('registration_user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_register');
        }

        // Récupérer l'utilisateur
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        // Récupérer l'ID de l'abonnement en attente
        $subscriptionId = $session->get('pending_subscription_id');
        if (!$subscriptionId) {
            return $this->redirectToRoute('app_register_subscription');
        }

        // Récupérer l'abonnement
        $subscription = $entityManager->getRepository('App\Entity\Subscription')->find($subscriptionId);
        if (!$subscription) {
            $this->addFlash('error', 'Abonnement non trouvé.');
            return $this->redirectToRoute('app_register_subscription');
        }

        try {
            // Créer l'abonnement pour l'utilisateur
            $recruiterSubscription = $subscriptionService->createSubscription($user, $subscription);

            // Envoyer un email de confirmation
            $emailService->sendSubscriptionConfirmation($user, $recruiterSubscription);

            // Si c'est un abonnement payant, envoyer également un reçu de paiement
            if ($subscription->getPrice() > 0) {
                $emailService->sendPaymentReceipt($user, $recruiterSubscription);
            }

            // Nettoyer la session
            $session->remove('registration_user_id');
            $session->remove('pending_subscription_id');

            $this->addFlash('success', 'Votre compte et votre abonnement ont été activés avec succès !');
            return $this->redirectToRoute('app_login');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'activation de votre abonnement.');
            return $this->redirectToRoute('app_register_subscription');
        }
    }

    #[Route('/register/subscription/cancel', name: 'app_register_subscription_cancel')]
    public function subscriptionCancel(SessionInterface $session): Response
    {
        $this->addFlash('info', 'Le paiement a été annulé. Vous pouvez réessayer ou choisir un autre abonnement.');
        return $this->redirectToRoute('app_register_subscription');
    }
}
