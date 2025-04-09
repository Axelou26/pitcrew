<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RecruiterSubscription;
use App\Entity\Subscription;
use App\Entity\User;
use App\Service\Payment\PaymentMode;
use App\Service\Payment\PaymentServiceInterface;
use App\Service\Payment\PaymentSessionFactory;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;
use stdClass;
use Exception;

class StripeService implements PaymentServiceInterface
{
    private PaymentMode $paymentMode;
    private ?StripeClient $stripeClient;
    private ParameterBagInterface $params;
    private EntityManagerInterface $entityManager;
    private SubscriptionService $subscriptionService;
    private EmailService $emailService;
    private LoggerInterface $logger;
    private PaymentSessionFactory $sessionFactory;

    public function __construct(
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager,
        SubscriptionService $subscriptionService,
        EmailService $emailService,
        LoggerInterface $logger,
        PaymentSessionFactory $sessionFactory
    ) {
        $this->params = $params;
        $this->entityManager = $entityManager;
        $this->subscriptionService = $subscriptionService;
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->sessionFactory = $sessionFactory;
        $this->initializePaymentMode();
    }

    private function initializePaymentMode(): void
    {
        $isOfflineMode = filter_var($this->params->get('stripe_offline_mode', false), FILTER_VALIDATE_BOOLEAN);

        if ($isOfflineMode) {
            $this->paymentMode = PaymentMode::OFFLINE;
            $this->stripeClient = null;
            return;
        }

        try {
            $stripeKey = $this->params->get('stripe_secret_key');
            if (!is_string($stripeKey)) {
                throw new RuntimeException('Invalid Stripe secret key');
            }

            $this->stripeClient = new StripeClient($stripeKey);
            $this->paymentMode = str_starts_with($stripeKey, 'sk_test_')
                ? PaymentMode::TEST
                : PaymentMode::LIVE;
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize Stripe client: ' . $e->getMessage());
            $this->paymentMode = PaymentMode::OFFLINE;
            $this->stripeClient = null;
        }
    }

    public function isTestMode(): bool
    {
        return $this->paymentMode === PaymentMode::TEST;
    }

    public function isOfflineMode(): bool
    {
        return $this->paymentMode === PaymentMode::OFFLINE;
    }

    public function createCheckoutSession(User $user, Subscription $subscription): Session|stdClass
    {
        if ($this->isOfflineMode()) {
            return $this->sessionFactory->createOfflineSession($this->generateSuccessUrl());
        }

        try {
            $stripeCustomerId = $this->ensureCustomerExists($user);
            return $this->createStripeCheckoutSession($stripeCustomerId, $user, $subscription);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la création de la session de paiement : ' . $e->getMessage());
            throw $e;
        }
    }

    private function ensureCustomerExists(User $user): string
    {
        $stripeCustomerId = $user->getStripeCustomerId();
        if ($stripeCustomerId) {
            return $stripeCustomerId;
        }

        $customer = $this->stripeClient->customers->create([
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

    private function createStripeCheckoutSession(string $customerId, User $user, Subscription $subscription): Session
    {
        $sessionParams = [
            'customer' => $customerId,
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
                    'unit_amount' => (int)($subscription->getPrice() * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateSuccessUrl(),
            'cancel_url' => $this->generateCancelUrl(),
            'metadata' => [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId()
            ]
        ];

        return $this->stripeClient->checkout->sessions->create($sessionParams);
    }

    public function handlePaymentSucceeded(array $payload): void
    {
        if ($this->isOfflineMode()) {
            return;
        }

        $session = $this->extractSessionData($payload);
        $metadata = $this->extractMetadata($session);

        $user = $this->entityManager->getRepository(User::class)->find($metadata['user_id']);
        $subscription = $this->entityManager->getRepository(Subscription::class)->find($metadata['subscription_id']);

        if (!$user || !$subscription) {
            throw new RuntimeException('User or subscription not found');
        }

        $this->subscriptionService->createSubscription($user, $subscription);
    }

    private function extractSessionData(array $payload): array
    {
        $session = $payload['data']['object'] ?? null;
        if (!is_array($session)) {
            throw new RuntimeException('Invalid session data in payload');
        }
        return $session;
    }

    private function extractMetadata(array $session): array
    {
        $metadata = $session['metadata'] ?? [];
        if (!is_array($metadata)) {
            throw new RuntimeException('Invalid metadata in session');
        }

        if (!isset($metadata['user_id'], $metadata['subscription_id'])) {
            throw new RuntimeException('Missing metadata in Stripe session');
        }

        return $metadata;
    }

    private function generateSuccessUrl(): string
    {
        return $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function generateCancelUrl(): string
    {
        return $this->generateUrl('app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->params->get('router')->generate($route, $parameters, $referenceType);
    }
}
