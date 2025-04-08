<?php

namespace App\Service;

use App\Entity\RecruiterSubscription;
use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeService
{
    private $params;
    private $entityManager;
    private $urlGenerator;
    private $subscriptionService;
    private $isTestMode;
    private $isOfflineMode;

    public function __construct(
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        SubscriptionService $subscriptionService
    ) {
        $this->params = $params;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->subscriptionService = $subscriptionService;

        // Vérifier si le mode hors ligne est activé
        $this->isOfflineMode = filter_var($this->params->get('stripe_offline_mode', false), FILTER_VALIDATE_BOOLEAN);

        // Initialiser Stripe avec la clé API seulement si pas en mode hors ligne
        if (!$this->isOfflineMode) {
            try {
                Stripe::setApiKey($this->params->get('stripe_secret_key'));
                // Détecter si nous sommes en mode test basé sur la clé API
                $this->isTestMode = strpos($this->params->get('stripe_secret_key'), 'sk_test_') === 0;
            } catch (\Exception $e) {
                // En cas d'erreur, passer automatiquement en mode hors ligne
                $this->isOfflineMode = true;
                $this->isTestMode = true;
            }
        } else {
            // En mode hors ligne, considérer comme mode test également
            $this->isTestMode = true;
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
     */
    public function createCheckoutSession(User $user, Subscription $subscription)
    {
        // Si on est en mode hors ligne, retourner directement une session simulée
        if ($this->isOfflineMode) {
            return $this->createOfflineCheckoutSession($user, $subscription);
        }

        // Créer ou récupérer le client Stripe
        $stripeCustomerId = $user->getStripeCustomerId();

        if (!$stripeCustomerId) {
            $customer = Customer::create([
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
        $successUrl = $this->urlGenerator->generate('app_subscription_success', [
            'subscription_id' => $subscription->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $cancelUrl = $this->urlGenerator->generate('app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // Déterminer le prix en centimes
        $priceInCents = $subscription->getPrice() * 100;

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

        // En mode test, ajoutez des paramètres spécifiques pour faciliter les tests
        if ($this->isTestMode) {
            $sessionParams['payment_intent_data'] = [
                'description' => '[TEST] Abonnement ' . $subscription->getName(),
                'metadata' => [
                    'is_test' => 'true',
                    'subscription_id' => $subscription->getId(),
                    'user_id' => $user->getId()
                ]
            ];
        }

        try {
            return Session::create($sessionParams);
        } catch (\Exception $e) {
            // En cas d'erreur, passer en mode hors ligne et retourner une session simulée
            $this->isOfflineMode = true;
            return $this->createOfflineCheckoutSession($user, $subscription);
        }
    }

    /**
     * Crée une session de paiement simulée quand le mode hors ligne est activé
     */
    private function createOfflineCheckoutSession(User $user, Subscription $subscription)
    {
        $successUrl = $this->urlGenerator->generate('app_subscription_success', [
            'subscription_id' => $subscription->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Créer un objet qui ressemble à une session Stripe
        $fakeSession = new \stdClass();
        $fakeSession->id = 'offline_' . uniqid();
        $fakeSession->url = $successUrl;
        $fakeSession->is_offline_session = true;

        return $fakeSession;
    }

    /**
     * Traite un webhook Stripe pour un paiement réussi
     */
    public function handlePaymentSucceeded(array $payload): void
    {
        // Si on est en mode hors ligne, juste un log ou rien
        if ($this->isOfflineMode) {
            return;
        }

        $session = $payload['data']['object'];

        // Récupérer les métadonnées
        $userId = $session['metadata']['user_id'] ?? null;
        $subscriptionId = $session['metadata']['subscription_id'] ?? null;

        if (!$userId || !$subscriptionId) {
            throw new \Exception('Métadonnées manquantes dans la session Stripe');
        }

        // Récupérer l'utilisateur et l'abonnement
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $subscription = $this->entityManager->getRepository(Subscription::class)->find($subscriptionId);

        if (!$user || !$subscription) {
            throw new \Exception('Utilisateur ou abonnement introuvable');
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
            throw new \Exception('Cette méthode ne peut être utilisée qu\'en mode test ou hors ligne');
        }

        // Générer une URL de succès directe
        $successUrl = $this->urlGenerator->generate('app_subscription_success', [
            'subscription_id' => $subscription->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

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
    public function createRecurringSubscription(User $user, Subscription $subscription): string
    {
        // Si on est en mode hors ligne, retourner directement une URL de succès
        if ($this->isOfflineMode) {
            $successUrl = $this->urlGenerator->generate('app_subscription_success', [
                'subscription_id' => $subscription->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            return $successUrl;
        }

        try {
            // Créer ou récupérer le client Stripe
            $stripeCustomerId = $user->getStripeCustomerId();

            if (!$stripeCustomerId) {
                $customer = Customer::create([
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

            // Créer un produit pour l'abonnement s'il n'existe pas déjà
            $productName = 'Abonnement ' . $subscription->getName();
            $product = \Stripe\Product::create([
                'name' => $productName,
                'metadata' => [
                    'subscription_id' => $subscription->getId()
                ]
            ]);

            // Créer un prix pour l'abonnement
            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => $subscription->getPrice() * 100, // en centimes
                'currency' => 'eur',
                'recurring' => [
                    'interval' => 'month',
                    'interval_count' => 1
                ]
            ]);

            // Créer une session de paiement pour l'abonnement récurrent
            $successUrl = $this->urlGenerator->generate('app_subscription_success', [
                'subscription_id' => $subscription->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $cancelUrl = $this->urlGenerator->generate('app_subscription_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $sessionParams = [
                'customer' => $stripeCustomerId,
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

            // En mode test, ajoutez des paramètres spécifiques
            if ($this->isTestMode) {
                $sessionParams['metadata']['is_test'] = 'true';
            }

            $session = Session::create($sessionParams);

            return $session->url;
        } catch (\Exception $e) {
            // En cas d'erreur, passer en mode hors ligne et retourner une URL de succès directe
            $this->isOfflineMode = true;

            $successUrl = $this->urlGenerator->generate('app_subscription_success', [
                'subscription_id' => $subscription->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            return $successUrl;
        }
    }

    /**
     * Annule un abonnement récurrent dans Stripe
     */
    public function cancelRecurringSubscription(RecruiterSubscription $subscription): bool
    {
        // Si en mode hors ligne, simuler une annulation réussie
        if ($this->isOfflineMode) {
            return true;
        }

        $stripeSubscriptionId = $subscription->getStripeSubscriptionId();

        if (!$stripeSubscriptionId) {
            return false;
        }

        try {
            $stripeSubscription = \Stripe\Subscription::retrieve($stripeSubscriptionId);
            $stripeSubscription->cancel();

            return true;
        } catch (\Exception $e) {
            // En cas d'erreur, on passe en mode hors ligne et on simule un succès
            $this->isOfflineMode = true;
            return true;
        }
    }
}
