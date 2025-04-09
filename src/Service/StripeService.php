<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RecruiterSubscription;
use App\Entity\Subscription;
use App\Entity\User;
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

class StripeService
{
    private bool $isTestMode;
    private bool $isOfflineMode;
    private $stripe;
    private $params;
    private $entityManager;
    private $subscriptionService;
    private $emailService;
    private $logger;

    public function __construct(
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager,
        SubscriptionService $subscriptionService,
        EmailService $emailService,
        LoggerInterface $logger
    ) {
        $this->params = $params;
        $this->entityManager = $entityManager;
        $this->subscriptionService = $subscriptionService;
        $this->emailService = $emailService;
        $this->logger = $logger;

        // Vérifier si le mode hors ligne est activé
        $this->isOfflineMode = filter_var($this->params->get('stripe_offline_mode', false), FILTER_VALIDATE_BOOLEAN);

        // Initialiser Stripe avec la clé API seulement si pas en mode hors ligne
        $this->isTestMode = $this->isOfflineMode; // Par défaut, mode test si hors ligne
        if (!$this->isOfflineMode) {
            try {
                $stripeKey = $this->params->get('stripe_secret_key');
                if (!is_string($stripeKey)) {
                    throw new RuntimeException('Invalid Stripe secret key');
                }
                $this->stripe = new StripeClient($stripeKey);
                // Détecter si nous sommes en mode test basé sur la clé API
                $this->isTestMode = str_starts_with($stripeKey, 'sk_test_');
            } catch (Exception $e) {
                // En cas d'erreur, passer automatiquement en mode hors ligne
                $this->isOfflineMode = true;
                $this->isTestMode = true; // Mode test si hors ligne
            }
        }
    }

    /**
     * Vérifie si le service est en mode test
     */
    public function isTestMode(): bool
    {
        return $this->isTestMode;
    }

    /**
     * Vérifie si le service est en mode hors ligne
     */
    public function isOfflineMode(): bool
    {
        return $this->isOfflineMode;
    }

    /**
     * Crée une session de paiement Stripe pour un abonnement
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCheckoutSession(User $user, Subscription $subscription): \Stripe\Checkout\Session
    {
        try {
            // Créer ou récupérer le client Stripe
            $stripeCustomerId = $user->getStripeCustomerId();

            if (!$stripeCustomerId) {
                $customer = $this->stripe->customers->create([
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

            // Créer la session de paiement
            $successUrl = $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

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

            return $this->stripe->checkout->sessions->create($sessionParams);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la session de paiement : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crée une session de paiement simulée quand le mode hors ligne est activé
     */
    private function createOfflineCheckoutSession(User $user, Subscription $subscription): stdClass
    {
        $successUrl = $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $fakeSession = new stdClass();
        $fakeSession->id = 'offline_' . uniqid();
        $fakeSession->url = $successUrl;
        $fakeSession->is_offline_session = true;

        return $fakeSession;
    }

    /**
     * Traite un webhook Stripe pour un paiement réussi
     * @param array<string, mixed> $payload
     * @throws RuntimeException
     */
    public function handlePaymentSucceeded(array $payload): void
    {
        // Si on est en mode hors ligne, juste un log ou rien
        if ($this->isOfflineMode) {
            return;
        }

        $session = $payload['data']['object'] ?? null;
        if (!is_array($session)) {
            throw new RuntimeException('Invalid session data in payload');
        }

        // Récupérer les métadonnées
        $metadata = $session['metadata'] ?? [];
        if (!is_array($metadata)) {
            throw new RuntimeException('Invalid metadata in session');
        }

        $userId = $metadata['user_id'] ?? null;
        $subscriptionId = $metadata['subscription_id'] ?? null;

        if (!$userId || !$subscriptionId) {
            throw new RuntimeException('Missing metadata in Stripe session');
        }

        // Récupérer l'utilisateur et l'abonnement
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $subscription = $this->entityManager->getRepository(Subscription::class)->find($subscriptionId);

        if (!$user || !$subscription) {
            throw new RuntimeException('User or subscription not found');
        }

        // Créer l'abonnement pour l'utilisateur
        $this->subscriptionService->createSubscription($user, $subscription);
    }

    /**
     * Crée une session de test qui simule un paiement sans passer par Stripe
     * À utiliser uniquement en développement
     */
    public function createTestCheckoutSession(User $user, Subscription $subscription): array
    {
        if (!$this->isTestMode && !$this->isOfflineMode) {
            throw new Exception('Cette méthode ne peut être utilisée qu\'en mode test ou hors ligne');
        }

        // Générer une URL de succès directe
        $successUrl = $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // Retourner un faux objet de session
        return [
            'id' => 'test_session_' . uniqid(),
            'url' => $successUrl,
            'is_test_session' => true
        ];
    }

    /**
     * Crée un abonnement récurrent avec Stripe
     */
    public function createRecurringSubscription(User $user, Subscription $subscription): \Stripe\Checkout\Session
    {
        try {
            // Créer ou récupérer le client Stripe
            $stripeCustomerId = $user->getStripeCustomerId();

            if (!$stripeCustomerId) {
                $customer = $this->stripe->customers->create([
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

            // Créer le produit dans Stripe
            $product = $this->stripe->products->create([
                'name' => 'Abonnement ' . $subscription->getName(),
                'description' => 'Abonnement ' . $subscription->getName() . ' pour ' . $subscription->getDuration() . ' jours',
                'metadata' => [
                    'subscription_id' => $subscription->getId()
                ]
            ]);

            // Créer le prix dans Stripe
            $price = $this->stripe->prices->create([
                'unit_amount' => (int)($subscription->getPrice() * 100),
                'currency' => 'eur',
                'recurring' => [
                    'interval' => 'month',
                    'interval_count' => 1
                ],
                'product' => $product->id,
            ]);

            // Créer la session de paiement
            $successUrl = $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $sessionParams = [
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $price->id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId()
                ]
            ];

            return $this->stripe->checkout->sessions->create($sessionParams);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'abonnement récurrent : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Annule un abonnement récurrent dans Stripe
     */
    public function cancelRecurringSubscription(RecruiterSubscription $subscription): void
    {
        try {
            if ($subscription->getStripeSubscriptionId()) {
                $this->stripe->subscriptions->cancel($subscription->getStripeSubscriptionId());
            }
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de l\'annulation de l\'abonnement : ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->generateUrl('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
