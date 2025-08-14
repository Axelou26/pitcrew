<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Applicant;
use App\Entity\Recruiter;
use App\Entity\Subscription;
use App\Entity\User;
use App\Form\Processor\ApplicantFieldProcessor;
use App\Form\Processor\RecruiterFieldProcessor;
use App\Form\Processor\UserFieldProcessorInterface;
use App\Form\RegistrationFormType;
use App\Form\UserTypeFormType;
use App\Service\OnboardingService;
use App\Service\RegistrationManager;
use App\Service\StripeService;
use App\Service\UserRegistrationValidator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationManager $registrationManager,
        private readonly StripeService $stripeService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, SessionInterface $session): Response
    {
        // Nettoyer la session si l'utilisateur revient à la première étape
        $session->remove('registration_user_id');
        $session->remove('registration_user_type');
        $session->remove('pending_subscription_id');

        $form = $this->createForm(UserTypeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userType = $form->get('userType')->getData();
            $session->set('registration_user_type', $userType);

            return $this->redirectToRoute('app_register_details');
        }

        return $this->render('registration/user_type.html.twig', [
            'userTypeForm' => $form->createView(),
        ]);
    }

    #[Route('/register/details', name: 'app_register_details')]
    public function registerDetails(
        Request $request,
        SessionInterface $session,
        UserRegistrationValidator $validator
    ): Response {
        $userType = $session->get('registration_user_type');
        if (!$userType) {
            return $this->redirectToRoute('app_register');
        }

        $user = $this->createUserByType($userType);
        $form = $this->createRegistrationForm($user, $userType);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                return $this->handleValidForm($form, $user, $userType, $session, $validator);
            }
            return $this->renderRegistrationForm($form, $userType, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->renderRegistrationForm($form, $userType);
    }

    #[Route('/register/subscription', name: 'app_register_subscription')]
    #[Route('/subscription/register-subscription', name: 'app_register_subscription_alias')]
    public function registerSubscription(
        Request $request,
        SessionInterface $session
    ): Response {
        $user = $this->validateAndGetUser($session);
        if ($user instanceof Response) {
            return $user;
        }

        if ($request->isMethod('POST')) {
            return $this->handleSubscriptionPost($request, $user, $session);
        }

        return $this->displaySubscriptionForm();
    }

    #[Route('/register/subscription/success', name: 'app_register_subscription_success')]
    public function subscriptionSuccess(SessionInterface $session): Response
    {
        try {
            $userId         = $session->get('registration_user_id');
            $subscriptionId = $session->get('pending_subscription_id');

            if (!$userId || !$subscriptionId) {
                throw new RuntimeException('Session invalide');
            }

            $result = $this->registrationManager->handleSubscriptionSuccess(
                $userId,
                $subscriptionId,
                $this->stripeService->isOfflineMode()
            );

            $this->addFlash('success', $result['message']);

            // Nettoyer la session
            $session->remove('registration_user_id');
            $session->remove('registration_user_type');
            $session->remove('pending_subscription_id');

            return $this->redirectToRoute('app_email_verification_sent');
        } catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_register_subscription');
        }
    }

    #[Route('/register/email-verification-sent', name: 'app_register_email_verification_sent')]
    public function emailVerificationSent(): Response
    {
        return $this->render('registration/email_verification_sent.html.twig');
    }

    #[Route('/onboarding', name: 'app_onboarding')]
    public function onboarding(OnboardingService $onboardingService): Response
    {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Obtenir les recommandations personnalisées
        $recommendations = $onboardingService->getPersonalizedRecommendations($user);

        // Obtenir les étapes d'onboarding
        $steps = $onboardingService->getOnboardingSteps($user);

        // Obtenir les statistiques
        $stats = $onboardingService->getOnboardingStats();

        return $this->render('registration/onboarding.html.twig', [
            'recommendations' => $recommendations,
            'steps'           => $steps,
            'stats'           => $stats,
            'user'            => $user,
        ]);
    }

    #[Route('/register/subscription/cancel', name: 'app_register_subscription_cancel')]
    public function subscriptionCancel(SessionInterface $session): Response
    {
        $session->remove('registration_user_id');
        $session->remove('registration_user_type');
        $session->remove('pending_subscription_id');

        $this->addFlash('info', 'Le processus d\'inscription a été annulé.');

        return $this->redirectToRoute('app_register');
    }

    #[Route('/register/subscription/clean', name: 'app_register_subscription_clean')]
    public function cleanupSubscriptions(
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            // Nettoyer manuellement les abonnements en suivant la logique de CleanupSubscriptionsCommand
            $existingSubscriptions = $entityManager->getRepository(Subscription::class)->findAll();

            // Supprimer tous les abonnements existants
            foreach ($existingSubscriptions as $subscription) {
                $entityManager->remove($subscription);
            }
            $entityManager->flush();

            // Créer les 3 abonnements standard
            $subscriptions = [
                [
                    'name' => 'Basic',
                    'price' => 0,
                    'duration' => 30,
                    'maxJobOffers' => 3,
                    'features' => [
                        'Publication de 3 offres d\'emploi maximum',
                        'Accès aux candidatures de base',
                        'Messagerie limitée avec les candidats',
                        'Profil standard d\'entreprise'
                    ],
                ],
                [
                    'name' => 'Premium',
                    'price' => 49,
                    'duration' => 30,
                    'maxJobOffers' => null,
                    'features' => [
                        'Offres d\'emploi illimitées',
                        'Mise en avant de vos offres',
                        'Accès complet aux CV des candidats',
                        'Messagerie illimitée',
                        'Statistiques de base sur vos offres',
                        'Profil entreprise amélioré'
                    ],
                ],
                [
                    'name' => 'Business',
                    'price' => 99,
                    'duration' => 30,
                    'maxJobOffers' => null,
                    'features' => [
                        'Offres d\'emploi illimitées',
                        'Recherche avancée de candidats',
                        'Recommandations automatiques de profils',
                        'Statistiques détaillées sur vos performances',
                        'Badge vérifié sur votre profil',
                        'Support prioritaire 24/7'
                    ],
                ],
            ];

            foreach ($subscriptions as $data) {
                $subscription = new Subscription();
                $subscription->setName($data['name']);
                $subscription->setPrice($data['price']);
                $subscription->setDuration($data['duration']);
                $subscription->setMaxJobOffers($data['maxJobOffers']);
                $subscription->setFeatures($data['features']);
                $subscription->setIsActive(true);

                $entityManager->persist($subscription);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Les abonnements ont été nettoyés avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du nettoyage des abonnements : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_register_subscription');
    }

    private function createUserByType(string $userType): User
    {
        return match ($userType) {
            User::ROLE_RECRUTEUR => new Recruiter(),
            User::ROLE_POSTULANT => new Applicant(),
            default              => new User(),
        };
    }

    private function createRegistrationForm(User $user, string $userType): FormInterface
    {
        return $this->createForm(RegistrationFormType::class, $user, [
            'user_type' => $userType,
        ]);
    }

    private function handleValidForm(
        FormInterface $form,
        User $user,
        string $userType,
        SessionInterface $session,
        UserRegistrationValidator $validator
    ): Response {
        $errors = $validator->validateRegistration($user, $form);
        if ($errors !== null) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->renderRegistrationForm($form, $userType, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->processUserSpecificFields($user, $form, $userType);
        $this->registrationManager->createUser(
            $user,
            $form->get('plainPassword')->getData(),
            $userType
        );

        if ($userType === User::ROLE_RECRUTEUR) {
            $session->set('registration_user_id', $user->getId());
            return $this->redirectToRoute('app_register_subscription');
        }

        $session->remove('registration_user_type');
        return $this->redirectToRoute('app_email_verification_sent');
    }

    private function renderRegistrationForm(
        FormInterface $form,
        string $userType,
        int $status = Response::HTTP_OK
    ): Response {
        return $this->render('registration/register_details.html.twig', [
            'registrationForm' => $form->createView(),
            'userType'         => $userType,
        ], new Response(null, $status));
    }

    private function validateAndGetUser(SessionInterface $session): Response|User
    {
        $userId = $session->get('registration_user_id');
        if (!$userId) {
            // Si pas d'ID utilisateur en session, mais qu'on est connecté et recruteur, utiliser l'utilisateur courant
            $currentUser = $this->getUser();
            if ($currentUser && $currentUser instanceof Recruiter) {
                return $currentUser;
            }

            $this->addFlash('error', 'Session d\'inscription invalide. Veuillez recommencer.');
            return $this->redirectToRoute('app_register');
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé. Veuillez recommencer.');
            return $this->redirectToRoute('app_register');
        }

        return $user;
    }

    private function handleSubscriptionPost(Request $request, User $user, SessionInterface $session): Response
    {
        // Vérifier si un ID d'abonnement est déjà en session (pour éviter les doublons)
        if ($session->get('pending_subscription_id')) {
            $this->addFlash('info', 'Vous avez déjà un abonnement en cours de traitement.');
            return $this->redirectToRoute('app_register_subscription');
        }

        $subscription = $this->validateSubscription($request);
        if ($subscription instanceof Response) {
            return $subscription;
        }

        // Stocker l'ID de l'abonnement dans la session
        $session->set('pending_subscription_id', $subscription->getId());

        $result = $this->registrationManager->handleSubscriptionSelection(
            $user,
            $subscription,
            $this->stripeService->isTestMode(),
            $this->stripeService->isOfflineMode()
        );

        return $this->handleSubscriptionResult($result);
    }

    private function validateSubscription(Request $request): Response|Subscription
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('subscription_form', $request->request->get('_token'))) {
            $this->addFlash('error', 'Session invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_register_subscription');
        }

        $subscriptionId = $request->request->get('subscription');
        if (!$subscriptionId) {
            $this->addFlash('error', 'Veuillez sélectionner un abonnement.');
            return $this->redirectToRoute('app_register_subscription');
        }

        $subscription = $this->entityManager->getRepository(Subscription::class)->find($subscriptionId);
        if (!$subscription) {
            $this->addFlash('error', 'Abonnement invalide.');
            return $this->redirectToRoute('app_register_subscription');
        }

        return $subscription;
    }

    private function handleSubscriptionResult(array $result): Response
    {
        if (isset($result['error'])) {
            $this->addFlash('error', $result['error']);
        }
        if (isset($result['message'])) {
            $this->addFlash('info', $result['message']);
        }
        if (isset($result['redirect_url'])) {
            return $this->redirect($result['redirect_url']);
        }

        return $this->redirectToRoute('app_register_' . ($result['redirect'] ?? 'subscription'));
    }

    private function displaySubscriptionForm(): Response
    {
        // Utiliser la méthode findActiveSubscriptions du repository pour éviter les doublons
        $subscriptions = $this->entityManager->getRepository(Subscription::class)
            ->findActiveSubscriptions();

        // Déduplication par nom si nécessaire
        $uniqueSubscriptions = [];
        $processedNames = [];

        foreach ($subscriptions as $subscription) {
            $name = $subscription->getName();
            if (!in_array($name, $processedNames)) {
                $uniqueSubscriptions[] = $subscription;
                $processedNames[] = $name;
            }
        }

        return $this->render('registration/select_subscription.html.twig', [
            'subscriptions'   => $uniqueSubscriptions,
            'is_test_mode'    => $this->stripeService->isTestMode(),
            'is_offline_mode' => $this->stripeService->isOfflineMode(),
            'required'        => true,
        ]);
    }

    /**
     * Traite les champs spécifiques selon le type d'utilisateur.
     * @param mixed $form
     */
    private function processUserSpecificFields(User $user, $form, string $userType): void
    {
        /** @var UserFieldProcessorInterface[] $processors */
        $processors = [
            new RecruiterFieldProcessor(),
            new ApplicantFieldProcessor(),
        ];

        foreach ($processors as $processor) {
            if ($processor->supports($userType)) {
                $processor->processFields($user, $form);
                break;
            }
        }
    }
}
