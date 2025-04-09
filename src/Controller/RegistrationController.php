<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Subscription;
use App\Form\RegistrationFormType;
use App\Form\UserTypeFormType;
use App\Service\RegistrationManager;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationManager $registrationManager,
        private readonly StripeService $stripeService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, SessionInterface $session): Response
    {
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
        SessionInterface $session
    ): Response {
        $userType = $session->get('registration_user_type');
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
            return $this->handleSubscriptionPost($request, $user);
        }

        return $this->displaySubscriptionForm();
    }

    private function validateAndGetUser(SessionInterface $session): Response|User
    {
        $userId = $session->get('registration_user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_register');
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        return $user;
    }

    private function handleSubscriptionPost(Request $request, User $user): Response
    {
        $subscription = $this->validateAndGetSubscription($request);
        if ($subscription instanceof Response) {
            return $subscription;
        }

        $result = $this->registrationManager->handleSubscriptionSelection(
            $user,
            $subscription,
            $request->query->has('test_mode') && $this->stripeService->isTestMode(),
            $this->stripeService->isOfflineMode()
        );

        return $this->handleSubscriptionResult($result);
    }

    private function validateAndGetSubscription(Request $request): Response|Subscription
    {
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
        $subscriptions = $this->entityManager->getRepository(Subscription::class)
            ->findBy(['isActive' => true], ['price' => 'ASC']);

        return $this->render('registration/select_subscription.html.twig', [
            'subscriptions' => $subscriptions,
            'is_test_mode' => $this->stripeService->isTestMode(),
            'is_offline_mode' => $this->stripeService->isOfflineMode(),
            'required' => true
        ]);
    }

    #[Route('/register/subscription/success', name: 'app_register_subscription_success')]
    public function subscriptionSuccess(SessionInterface $session): Response
    {
        try {
            $userId = $session->get('registration_user_id');
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
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_register_subscription');
        }
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
}
