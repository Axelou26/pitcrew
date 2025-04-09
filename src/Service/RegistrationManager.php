<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Subscription;
use App\Entity\RecruiterSubscription;
use App\Repository\UserRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\RecruiterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\StripeClient;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use RuntimeException;

class RegistrationManager
{
    private StripeClient $stripe;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly StripeService $stripeService,
        private readonly SubscriptionService $subscriptionService,
        private readonly EmailService $emailService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $stripeSecretKey
    ) {
        $this->stripe = new StripeClient($this->stripeSecretKey);
    }

    public function createUser(User $user, string $plainPassword, string $userType): void
    {
        $user->setRoles([$userType]);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $plainPassword)
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function handleSubscriptionSelection(
        User $user,
        Subscription $subscription,
        bool $isTestMode = false,
        bool $isOfflineMode = false
    ): array {
        if ($this->shouldProcessDirectly($subscription, $isTestMode, $isOfflineMode)) {
            return $this->processDirectSubscription($user, $subscription, $isTestMode, $isOfflineMode);
        }

        return $this->processStripeSubscription($user, $subscription);
    }

    public function handleSubscriptionSuccess(
        string $userId,
        string $subscriptionId,
        bool $isOfflineMode = false
    ): array {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new RuntimeException('Utilisateur non trouvé');
        }

        $subscription = $this->entityManager->getRepository(Subscription::class)->find($subscriptionId);
        if (!$subscription) {
            throw new RuntimeException('Abonnement non trouvé');
        }

        $recruiterSub = $this->subscriptionService->createSubscription($user, $subscription);

        if ($isOfflineMode) {
            $recruiterSub->setPaymentStatus('offline_mode');
            $this->entityManager->flush();
        }

        $this->emailService->sendRegistrationConfirmation($user);

        if ($subscription->getPrice() > 0) {
            $this->emailService->sendPaymentReceipt($user, $recruiterSub);
        }

        return [
            'message' => sprintf(
                'Votre compte a été créé et votre abonnement %s a été activé avec succès !',
                $subscription->getName()
            )
        ];
    }

    private function shouldProcessDirectly(
        Subscription $subscription,
        bool $isTestMode,
        bool $isOfflineMode
    ): bool {
        return $subscription->getPrice() == 0 || $isTestMode || $isOfflineMode;
    }

    private function processDirectSubscription(
        User $user,
        Subscription $subscription,
        bool $isTestMode,
        bool $isOfflineMode
    ): array {
        $recruiterSub = $this->subscriptionService->createSubscription($user, $subscription);
        $this->emailService->sendRegistrationConfirmation($user);

        if ($isTestMode || $isOfflineMode) {
            return [
                'redirect' => 'email_verification_sent',
                'message' => 'Votre compte a été créé et votre abonnement a été activé en mode test !'
            ];
        }

        return [
            'redirect' => 'email_verification_sent',
            'message' => 'Votre compte a été créé et votre abonnement gratuit a été activé avec succès !'
        ];
    }

    private function processStripeSubscription(User $user, Subscription $subscription): array
    {
        try {
            $stripeCustomerId = $this->ensureStripeCustomer($user);
            $checkoutSession = $this->createRegistrationCheckoutSession($user, $subscription, $stripeCustomerId);

            return ['redirect_url' => $checkoutSession->url];
        } catch (\Exception $e) {
            return [
                'redirect' => 'register',
                'error' => 'Erreur lors de la création de la session de paiement : ' . $e->getMessage()
            ];
        }
    }

    private function ensureStripeCustomer(User $user): string
    {
        if ($user->getStripeCustomerId()) {
            return $user->getStripeCustomerId();
        }

        $customer = $this->stripe->customers->create([
            'email' => $user->getEmail(),
            'name' => $user->getFullName(),
            'metadata' => [
                'user_id' => $user->getId()
            ]
        ]);

        $user->setStripeCustomerId($customer->id);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $customer->id;
    }

    private function createRegistrationCheckoutSession(
        User $user,
        Subscription $subscription,
        string $stripeCustomerId
    ): object {
        $successUrl = $this->urlGenerator->generate(
            'app_register_subscription_success',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $cancelUrl = $this->urlGenerator->generate(
            'app_register_subscription_cancel',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $priceInCents = (int)($subscription->getPrice() * 100);

        return $this->stripe->checkout->sessions->create([
            'customer' => $stripeCustomerId,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Abonnement ' . $subscription->getName(),
                        'description' => sprintf(
                            'Abonnement %s pour %s jours',
                            $subscription->getName(),
                            $subscription->getDuration()
                        ),
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
        ]);
    }
}
