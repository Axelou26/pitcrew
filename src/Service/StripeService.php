<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Service\Payment\PaymentMode;
use App\Service\Payment\PaymentServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeService implements PaymentServiceInterface
{
    private ?StripeClient $stripeClient = null;
    private EntityManagerInterface $entityManager;
    private SubscriptionService $subscriptionService;
    private EmailService $emailService;
    private $parameterBag;
    private $params;
    private $logger;
    private PaymentMode $paymentMode;

    public function __construct(ParameterBagInterface $parameterBag, LoggerInterface $logger)
    {
        $this->parameterBag = $parameterBag;
        $this->logger       = $logger;
        $this->params       = [
            'success_url'    => null,
            'cancel_url'     => null,
            'webhook_secret' => null,
            'api_key'        => null,
        ];

        $this->paymentMode = PaymentMode::OFFLINE; // Valeur par défaut
        $this->initializeStripeClient();
        $this->initializePaymentMode();
    }

    public function isTestMode(): bool
    {
        return $this->paymentMode === PaymentMode::TEST;
    }

    public function isOfflineMode(): bool
    {
        return $this->paymentMode === PaymentMode::OFFLINE;
    }

    public function createCustomer(User $user): string
    {
        if ($this->stripeClient === null) {
            throw new RuntimeException('Stripe client not initialized');
        }

        $customer = $this->stripeClient->customers->create([
            'email'    => $user->getEmail(),
            'name'     => $user->getFirstName() . ' ' . $user->getLastName(),
            'metadata' => [
                'user_id' => $user->getId(),
            ],
        ]);

        return $customer->id;
    }

    public function createCheckoutSession(User $user, Subscription $subscription): object
    {
        if ($this->stripeClient === null) {
            throw new RuntimeException('Stripe client not initialized');
        }

        $session = $this->stripeClient->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name' => $subscription->getName(),
                    ],
                    'unit_amount' => $subscription->getPrice() * 100, // Stripe utilise les centimes
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => $this->generateUrl('app_subscription_success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url'  => $this->generateUrl('app_subscription_cancel'),
            'metadata'    => [
                'user_id'         => $user->getId(),
                'subscription_id' => $subscription->getId(),
            ],
        ]);

        return $session;
    }

    /**
     * Gère le succès d'un paiement.
     *
     * @param array<string, mixed> $payload
     */
    public function handlePaymentSucceeded(array $payload): void
    {
        if ($this->isOfflineMode()) {
            return;
        }

        $session  = $this->extractSessionData($payload);
        $metadata = $this->extractMetadata($session);

        $user         = $this->entityManager->getRepository(User::class)->find($metadata['user_id']);
        $subscription = $this->entityManager->getRepository(Subscription::class)->find($metadata['subscription_id']);

        if (!$user || !$subscription) {
            throw new RuntimeException('User or subscription not found');
        }

        $this->subscriptionService->createSubscription($user, $subscription);
    }

    /**
     * Génère l'URL de succès pour le paiement.
     */
    public function generateSuccessUrl(): string
    {
        // URL par défaut en cas d'absence dans la configuration
        $defaultUrl = 'https://pitcrew.com/payment/success';

        // Utiliser l'URL configurée ou l'URL par défaut
        $successUrl = $this->parameterBag->get('stripe_success_url', $defaultUrl);

        return $successUrl;
    }

    /**
     * Génère l'URL d'annulation pour le paiement.
     */
    public function generateCancelUrl(): string
    {
        // URL par défaut en cas d'absence dans la configuration
        $defaultUrl = 'https://pitcrew.com/payment/cancel';

        // Utiliser l'URL configurée ou l'URL par défaut
        return $this->parameterBag->get('stripe_cancel_url', $defaultUrl);
    }

    public function cancelRecurringSubscription(string $stripeSubscriptionId): bool
    {
        try {
            if ($this->stripeClient === null) {
                throw new RuntimeException('Stripe client not initialized');
            }

            $subscription = $this->stripeClient->subscriptions->retrieve($stripeSubscriptionId);
            $subscription->cancel();

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de l\'annulation de l\'abonnement Stripe: ' . $e->getMessage());

            return false;
        }
    }

    public function generateCheckoutUrl(string $sessionId): string
    {
        if (!$sessionId) {
            throw new InvalidArgumentException('Session ID cannot be empty');
        }

        $baseUrl = $this->parameterBag->get('stripe_success_url', 'https://pitcrew.com/checkout/success');

        return $baseUrl . '?session_id=' . $sessionId;
    }

    private function initializeStripeClient(): void
    {
        $apiKey = $this->parameterBag->get('stripe_secret_key');
        if (!$apiKey) {
            $this->logger->error('Stripe API key not configured');

            return;
        }

        try {
            $this->stripeClient = new StripeClient($apiKey);
        } catch (Exception $e) {
            $this->logger->error('Failed to initialize Stripe client: ' . $e->getMessage());
            $this->stripeClient = null;
        }
    }

    private function initializePaymentMode(): void
    {
        $isOfflineMode = filter_var($this->parameterBag->get('stripe_offline_mode', false), \FILTER_VALIDATE_BOOLEAN);

        if ($isOfflineMode) {
            $this->paymentMode  = PaymentMode::OFFLINE;
            $this->stripeClient = null;

            return;
        }

        try {
            $stripeKey = $this->parameterBag->get('stripe_secret_key');
            if (!is_string($stripeKey)) {
                throw new RuntimeException('Invalid Stripe secret key');
            }

            $this->stripeClient = new StripeClient($stripeKey);
            $this->paymentMode  = str_starts_with($stripeKey, 'sk_test_')
                ? PaymentMode::TEST
                : PaymentMode::LIVE;
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Stripe client: ' . $e->getMessage());
            $this->paymentMode  = PaymentMode::OFFLINE;
            $this->stripeClient = null;
        }
    }

    private function ensureCustomerExists(User $user): string
    {
        $stripeCustomerId = $user->getStripeCustomerId();
        if ($stripeCustomerId) {
            return $stripeCustomerId;
        }

        if ($this->stripeClient === null) {
            throw new RuntimeException('Stripe client not initialized');
        }

        $customer = $this->stripeClient->customers->create([
            'email'    => $user->getEmail(),
            'name'     => $user->getFullName(),
            'metadata' => [
                'user_id' => $user->getId(),
            ],
        ]);

        $user->setStripeCustomerId($customer->id);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $customer->id;
    }

    private function createStripeCheckoutSession(string $customerId, User $user, Subscription $subscription): Session
    {
        if ($this->stripeClient === null) {
            throw new RuntimeException('Stripe client not initialized');
        }

        $sessionParams = [
            'customer'             => $customerId,
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name'        => 'Abonnement ' . $subscription->getName(),
                        'description' => sprintf(
                            'Abonnement %s pour %s jours',
                            $subscription->getName(),
                            $subscription->getDuration()
                        ),
                        'metadata' => [
                            'subscription_id' => $subscription->getId(),
                        ],
                    ],
                    'unit_amount' => (int) ($subscription->getPrice() * 100),
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => $this->generateSuccessUrl(),
            'cancel_url'  => $this->generateCancelUrl(),
            'metadata'    => [
                'user_id'         => $user->getId(),
                'subscription_id' => $subscription->getId(),
            ],
        ];

        return $this->stripeClient->checkout->sessions->create($sessionParams);
    }

    /**
     * Extrait les données de session depuis le payload.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function extractSessionData(array $payload): array
    {
        $session = $payload['data']['object'] ?? null;
        if (!is_array($session)) {
            throw new RuntimeException('Invalid session data in payload');
        }

        return $session;
    }

    /**
     * Extrait les métadonnées d'une session.
     *
     * @param array<string, mixed> $session
     *
     * @return array<string, mixed>
     */
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

    /**
     * Génère une URL.
     *
     * @param array<string, mixed> $parameters
     */
    private function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        // Cette méthode ne peut pas fonctionner correctement car nous n'avons pas le service router injecté
        // Il faudrait injecter le UrlGeneratorInterface dans le constructeur
        $baseUrl = $this->parameterBag->get('app_url', 'https://pitcrew.com');

        return $baseUrl . '/' . $route . '?' . http_build_query($parameters);
    }
}
