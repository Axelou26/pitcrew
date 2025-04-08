<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use App\Repository\RecruiterSubscriptionRepository;
use App\Service\StripeService;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\EmailService;
use App\Entity\User;
use App\Entity\RecruiterSubscription;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[Route('/subscription')]
#[IsGranted('ROLE_RECRUTEUR')]
class SubscriptionController extends AbstractController
{
    private $params;
    private $subscriptionService;
    private $stripeService;

    public function __construct(
        ParameterBagInterface $params,
        SubscriptionService $subscriptionService,
        StripeService $stripeService
    ) {
        $this->params = $params;
        $this->subscriptionService = $subscriptionService;
        $this->stripeService = $stripeService;
    }

    #[Route('/', name: 'app_subscription_plans')]
    public function plans(SubscriptionRepository $subscriptionRepository): Response
    {
        $subscriptions = $subscriptionRepository->findBy(['isActive' => true], ['price' => 'ASC']);

        // Récupérer l'abonnement actif de l'utilisateur
        $activeSubscription = $this->subscriptionService->getActiveSubscription($this->getUser());

        // Déterminer si nous sommes en mode test
        $isTestMode = $this->stripeService->isTestMode();
        $isOfflineMode = $this->stripeService->isOfflineMode();

        return $this->render('subscription/plans.html.twig', [
            'subscriptions' => $subscriptions,
            'activeSubscription' => $activeSubscription,
            'stripe_public_key' => $this->params->get('stripe_public_key'),
            'is_test_mode' => $isTestMode,
            'is_offline_mode' => $isOfflineMode
        ]);
    }

    #[Route('/select/{id}', name: 'app_subscription_select')]
    public function select(
        Subscription $subscription,
        SessionInterface $session,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur a déjà un abonnement actif
        $activeSubscription = $this->subscriptionService->getActiveSubscription($this->getUser());

        // Si l'utilisateur a déjà un abonnement du même type, l'informer
        if ($activeSubscription && $activeSubscription->getSubscription()->getId() === $subscription->getId()) {
            $this->addFlash('info', 'Vous êtes déjà abonné à ce plan.');
            return $this->redirectToRoute('app_subscription_manage');
        }

        // Option pour le mode test: bypass Stripe pour les tests
        $useTestMode = $request->query->has('test_mode') && $this->stripeService->isTestMode();

        // Si c'est un abonnement gratuit, en mode hors ligne ou useTestMode, l'activer directement
        if ($subscription->getPrice() == 0 || $useTestMode || $this->stripeService->isOfflineMode()) {
            // Si un abonnement actif existe, le désactiver
            if ($activeSubscription) {
                $this->subscriptionService->cancelSubscription($activeSubscription);
            }

            $newSubscription = $this->subscriptionService->createSubscription($this->getUser(), $subscription);

            // Si c'est un test ou mode hors ligne, marquer comme tel
            if ($useTestMode || $this->stripeService->isOfflineMode()) {
                $newSubscription->setPaymentStatus('test_mode');
                $entityManager->flush();

                $this
                    ->addFlash('success', 'Votre abonnement ' . $subscription
                    ->getName() . ' a été activé en mode test !');
            } else {
                $this
                    ->addFlash('success', 'Votre abonnement ' . $subscription
                    ->getName() . ' a été activé avec succès !');
            }

            return $this->redirectToRoute('app_dashboard');
        }

        // Pour les abonnements payants, créer une session de paiement Stripe et rediriger
        try {
            // Stocker l'ID de l'abonnement en session pour le traitement après paiement
            $session->set('pending_subscription_id', $subscription->getId());
            $session->set('is_subscription_change', $activeSubscription ? true : false);

            $stripeSession = $this->stripeService->createCheckoutSession($this->getUser(), $subscription);
            return $this->redirect($stripeSession->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création de la session de paiement : ' . $e->getMessage());
            return $this->redirectToRoute('app_subscription_plans');
        }
    }

    #[Route('/success/{subscription_id}', name: 'app_subscription_success')]
    public function success(
        string $subscription_id,
        SubscriptionRepository $subscriptionRepository,
        EmailService $emailService,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        $subscription = $subscriptionRepository->find($subscription_id);

        if (!$subscription) {
            throw $this->createNotFoundException('Abonnement non trouvé');
        }

        // Vérifier si c'est un changement d'abonnement ou un nouvel abonnement
        $isChange = $session->get('is_subscription_change', false);

        // Récupérer l'abonnement actif de l'utilisateur
        $activeSubscription = $this->subscriptionService->getActiveSubscription($this->getUser());

        // Si un abonnement actif existe et que c'est un changement, le désactiver
        if ($activeSubscription && $isChange) {
            $this->subscriptionService->cancelSubscription($activeSubscription);
        }

        // Créer le nouvel abonnement pour l'utilisateur
        $recruiterSubscription = $this->subscriptionService->createSubscription($this->getUser(), $subscription);

        // En mode hors ligne, marquer l'abonnement comme tel
        if ($this->stripeService->isOfflineMode()) {
            $recruiterSubscription->setPaymentStatus('offline_mode');
            $entityManager->flush();
        }

        // Envoyer un email de confirmation
        $emailService->sendSubscriptionConfirmation($this->getUser(), $recruiterSubscription);

        // Si c'est un abonnement payant, envoyer également un reçu de paiement
        if ($subscription->getPrice() > 0) {
            $emailService->sendPaymentReceipt($this->getUser(), $recruiterSubscription);
        }

        // Nettoyer la session
        $session->remove('pending_subscription_id');
        $session->remove('is_subscription_change');

        // Message différent selon qu'il s'agit d'un nouvel abonnement ou d'un changement
        if ($isChange) {
            $this
                ->addFlash('success', 'Votre abonnement a été mis à jour avec succès vers ' . $subscription
                ->getName() . ' !');
        } else {
            $this
                ->addFlash('success', 'Votre abonnement ' . $subscription
                ->getName() . ' a été activé avec succès !');
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/cancel', name: 'app_subscription_cancel')]
    public function cancel(SessionInterface $session): Response
    {
        // Nettoyer la session
        $session->remove('pending_subscription_id');
        $session->remove('is_subscription_change');

        $this->addFlash('info', 'Le processus de paiement a été annulé.');
        return $this->redirectToRoute('app_subscription_plans');
    }

    #[Route('/manage', name: 'app_subscription_manage')]
    public function manage(
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository,
        SubscriptionRepository $subscriptionRepository
    ): Response {
        // Récupérer l'abonnement actif de l'utilisateur
        $activeSubscription = $this->subscriptionService->getActiveSubscription($this->getUser());

        // Récupérer l'historique des abonnements
        $subscriptionHistory = $recruiterSubscriptionRepository->findBy(
            ['recruiter' => $this->getUser()],
            ['startDate' => 'DESC']
        );

        // Récupérer tous les abonnements disponibles pour permettre le changement
        $availableSubscriptions = $subscriptionRepository->findBy(['isActive' => true], ['price' => 'ASC']);

        return $this->render('subscription/manage.html.twig', [
            'activeSubscription' => $activeSubscription,
            'subscriptionHistory' => $subscriptionHistory,
            'availableSubscriptions' => $availableSubscriptions,
            'stripe_public_key' => $this->params->get('stripe_public_key'),
            'is_test_mode' => $this->stripeService->isTestMode(),
            'is_offline_mode' => $this->stripeService->isOfflineMode()
        ]);
    }

    #[Route('/change', name: 'app_subscription_change')]
    public function changeSubscription(): Response
    {
        // Rediriger vers la page des plans pour choisir un nouvel abonnement
        return $this->redirectToRoute('app_subscription_plans', [
            'change' => true
        ]);
    }

    #[Route('/cancel/{id}', name: 'app_subscription_cancel_subscription', methods: ['POST'])]
    public function cancelSubscription(
        string $id,
        Request $request,
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        $subscription = $recruiterSubscriptionRepository->find($id);

        if (!$subscription || $subscription->getRecruiter() !== $this->getUser()) {
            throw $this->createNotFoundException('Abonnement non trouvé');
        }

        if ($this->isCsrfTokenValid('cancel' . $subscription->getId(), $request->request->get('_token'))) {
            // Annuler l'abonnement dans Stripe si c'est un abonnement récurrent
            if ($subscription->getStripeSubscriptionId() && !$this->stripeService->isOfflineMode()) {
                $this->stripeService->cancelRecurringSubscription($subscription);
            }

            // Mettre à jour le statut de l'abonnement
            $subscription->setCancelled(true);
            $subscription->setAutoRenew(false);
            $entityManager->flush();

            // Envoyer un email de confirmation d'annulation
            $emailService->sendSubscriptionCancellationConfirmation($this->getUser(), $subscription);

            $this->addFlash('success', 'Votre abonnement a été annulé avec succès.');
        }

        return $this->redirectToRoute('app_subscription_manage');
    }

    #[Route('/webhook', name: 'app_subscription_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        EntityManagerInterface $entityManager,
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository,
        SubscriptionRepository $subscriptionRepository,
        EmailService $emailService
    ): Response {
        $payload = json_decode($request->getContent(), true);
        $sigHeader = $request->headers->get('Stripe-Signature');
        $endpointSecret = $this->params->get('stripe_webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $sigHeader,
                $endpointSecret
            );
        } catch (\Exception $e) {
            return new Response('Webhook Error: ' . $e->getMessage(), 400);
        }

        // Gérer les événements Stripe
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->stripeService->handlePaymentSucceeded($event->data->toArray());
                break;

            case 'invoice.payment_succeeded':
                // Gérer le renouvellement d'abonnement
                $invoice = $event->data->object;
                $stripeSubscriptionId = $invoice->subscription;
                $customerId = $invoice->customer;

                // Trouver l'utilisateur par son ID client Stripe
                $user = $entityManager->getRepository(User::class)->findOneBy(['stripeCustomerId' => $customerId]);

                if (!$user) {
                    return new Response('User not found', 200);
                }

                // Trouver l'abonnement par son ID Stripe
                $recruiterSubscription = $recruiterSubscriptionRepository
                    ->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);

                if ($recruiterSubscription) {
                    // Prolonger l'abonnement existant
                    $newEndDate = clone $recruiterSubscription->getEndDate();
                    $newEndDate->modify('+' . $recruiterSubscription->getSubscription()->getDuration() . ' days');
                    $recruiterSubscription->setEndDate($newEndDate);
                    $recruiterSubscription->setIsActive(true);
                    $recruiterSubscription->setCancelled(false);

                    $entityManager->persist($recruiterSubscription);
                    $entityManager->flush();

                    // Envoyer un email de confirmation de renouvellement
                    $emailService->sendSubscriptionConfirmation($user, $recruiterSubscription);
                }
                break;

            case 'customer.subscription.deleted':
                // Gérer l'annulation d'abonnement
                $stripeSubscription = $event->data->object;
                $stripeSubscriptionId = $stripeSubscription->id;

                // Trouver l'abonnement par son ID Stripe
                $recruiterSubscription = $recruiterSubscriptionRepository
                    ->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);

                if ($recruiterSubscription) {
                    // Marquer l'abonnement comme annulé
                    $recruiterSubscription->setCancelled(true);
                    $recruiterSubscription->setAutoRenew(false);

                    $entityManager->persist($recruiterSubscription);
                    $entityManager->flush();

                    // Envoyer un email de confirmation d'annulation
                    $emailService
                        ->sendSubscriptionCancellationConfirmation($recruiterSubscription
                        ->getRecruiter(), $recruiterSubscription);
                }
                break;

            case 'payment_intent.payment_failed':
                // Gérer l'échec de paiement
                $paymentIntent = $event->data->object;
                $customerId = $paymentIntent->customer;

                // Trouver l'utilisateur par son ID client Stripe
                $user = $entityManager->getRepository(User::class)->findOneBy(['stripeCustomerId' => $customerId]);

                if ($user) {
                    // Envoyer un email d'échec de paiement
                    $emailService->sendPaymentFailedNotification($user);
                }
                break;
        }

        return new Response('Webhook Handled', 200);
    }

    #[Route('/invoice/{id}', name: 'app_subscription_invoice')]
    public function invoice(
        string $id,
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository
    ): Response {
        $subscription = $recruiterSubscriptionRepository->find($id);

        if (!$subscription || $subscription->getRecruiter() !== $this->getUser()) {
            throw $this->createNotFoundException('Abonnement non trouvé');
        }

        // Générer un numéro de facture unique
        $invoiceNumber = 'FACT-' . date('Y') . '-' . str_pad($subscription->getId(), 6, '0', STR_PAD_LEFT);

        return $this->render('subscription/invoice.html.twig', [
            'subscription' => $subscription->getSubscription(),
            'user' => $this->getUser(),
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $subscription->getStartDate(),
            'payment_date' => $subscription->getStartDate(),
            'start_date' => $subscription->getStartDate(),
            'end_date' => $subscription->getEndDate(),
        ]);
    }

    /**
     * Route spéciale pour les tests qui permet d'activer directement un abonnement sans passer par Stripe
     * À utiliser uniquement en environnement de développement
     */
    #[Route('/test-direct-subscription/{id}', name: 'app_subscription_test_direct')]
    public function testDirectSubscription(
        Subscription $subscription,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        // Vérifier que nous sommes bien en mode test ou hors ligne
        if (!$this->stripeService->isTestMode() && !$this->stripeService->isOfflineMode()) {
            throw $this->createAccessDeniedException('Cette route n\'est disponible qu\'en mode test ou hors ligne');
        }

        // Récupérer l'abonnement actif de l'utilisateur
        $activeSubscription = $this->subscriptionService->getActiveSubscription($this->getUser());

        // Si un abonnement actif existe, le désactiver
        if ($activeSubscription) {
            $this->subscriptionService->cancelSubscription($activeSubscription);
        }

        // Créer un nouvel abonnement pour l'utilisateur (mode test)
        $recruiterSubscription = $this->subscriptionService->createSubscription($this->getUser(), $subscription);

        // Marquer cet abonnement comme créé en mode test
        $recruiterSubscription->setPaymentStatus('test_mode');
        $entityManager->persist($recruiterSubscription);
        $entityManager->flush();

        // Envoyer un email de confirmation (si configuré)
        $emailService->sendSubscriptionConfirmation($this->getUser(), $recruiterSubscription);

        if ($this->stripeService->isOfflineMode()) {
            $this
                ->addFlash('success', '[MODE HORS LIGNE] Votre abonnement ' . $subscription
                ->getName() . ' a été activé avec succès !');
        } else {
            $this
                ->addFlash('success', '[MODE TEST] Votre abonnement ' . $subscription
                ->getName() . ' a été activé avec succès !');
        }

        return $this->redirectToRoute('app_dashboard');
    }
}
