<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use App\Repository\RecruiterSubscriptionRepository;
use App\Service\StripeService;
use App\Service\SubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Stripe\Webhook;
use App\Service\StripeWebhookHandler;

#[Route('/subscription')]
#[IsGranted('ROLE_RECRUTEUR')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly StripeService $stripeService,
        private readonly ParameterBagInterface $params,
        private readonly SubscriptionRepository $subscriptionRepo,
        private readonly RecruiterSubscriptionRepository $recruiterSubRepo
    ) {
    }

    #[Route('/', name: 'app_subscription_plans')]
    public function plans(): Response
    {
        $subscriptions = $this->subscriptionRepo->findBy(['isActive' => true], ['price' => 'ASC']);

        return $this->render('subscription/plans.html.twig', [
            'subscriptions' => $subscriptions,
            'stripe_public_key' => $this->params->get('stripe_public_key'),
            'is_test_mode' => $this->stripeService->isTestMode(),
            'is_offline_mode' => $this->stripeService->isOfflineMode()
        ]);
    }

    #[Route('/select/{id}', name: 'app_subscription_select')]
    public function select(
        Subscription $subscription,
        SessionInterface $session,
        Request $request
    ): Response {
        $result = $this->subscriptionManager->handleSubscriptionSelection(
            $this->getUser(),
            $subscription,
            $session,
            $request->query->has('test_mode') && $this->stripeService->isTestMode(),
            $this->stripeService->isOfflineMode()
        );

        if (isset($result['error'])) {
            $this->addFlash('error', $result['error']);
        }
        if (isset($result['message'])) {
            $this->addFlash('info', $result['message']);
        }
        if (isset($result['redirect_url'])) {
            return $this->redirect($result['redirect_url']);
        }

        return $this->redirectToRoute('app_subscription_' . ($result['redirect'] ?? 'plans'));
    }

    #[Route('/success/{subscriptionId}', name: 'app_subscription_success')]
    public function success(
        string $subscriptionId,
        SessionInterface $session
    ): Response {
        try {
            $result = $this->subscriptionManager->handleSubscriptionSuccess(
                $subscriptionId,
                $this->getUser(),
                $session,
                $this->stripeService->isOfflineMode()
            );

            $this->addFlash('success', $result['message']);
            
            // Nettoyer la session
            $session->remove('pending_subscription_id');
            $session->remove('is_subscription_change');

            return $this->redirectToRoute('app_subscription_manage');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_subscription_plans');
        }
    }

    #[Route('/manage', name: 'app_subscription_manage')]
    public function manage(): Response
    {
        return $this->render('subscription/manage.html.twig', [
            'active_subscription' => $this->recruiterSubRepo->findActiveByUser($this->getUser()),
            'subscription_history' => $this->recruiterSubRepo->findByUser($this->getUser()),
            'available_subscriptions' => $this->subscriptionRepo->findBy(['isActive' => true], ['price' => 'ASC']),
            'stripe_public_key' => $this->params->get('stripe_public_key'),
            'is_test_mode' => $this->stripeService->isTestMode(),
            'is_offline_mode' => $this->stripeService->isOfflineMode()
        ]);
    }

    #[Route('/cancel/{subscriptionId}', name: 'app_subscription_cancel_subscription', methods: ['POST'])]
    public function cancelSubscription(
        string $subscriptionId,
        Request $request
    ): Response {
        $subscription = $this->recruiterSubRepo->find($subscriptionId);

        if (!$subscription || $subscription->getRecruiter() !== $this->getUser()) {
            throw $this->createNotFoundException('Abonnement non trouvé');
        }

        if ($this->isCsrfTokenValid('cancel' . $subscription->getId(), $request->request->get('_token'))) {
            $this->subscriptionManager->cancelSubscription($subscription, $this->getUser());
            $this->addFlash('success', 'Votre abonnement a été annulé avec succès.');
        }

        return $this->redirectToRoute('app_subscription_manage');
    }

    #[Route('/webhook', name: 'app_subscription_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        StripeWebhookHandler $webhookHandler
    ): Response {
        try {
            $event = $webhookHandler->constructEvent(
                $request->getContent(),
                $request->headers->get('Stripe-Signature')
            );
            
            $webhookHandler->handleEvent($event);
            return new Response('Webhook Handled', 200);
        } catch (\Exception $e) {
            return new Response('Webhook Error: ' . $e->getMessage(), 400);
        }
    }

    #[Route('/invoice/{subscriptionId}', name: 'app_subscription_invoice')]
    public function invoice(string $subscriptionId): Response
    {
        $subscription = $this->recruiterSubRepo->find($subscriptionId);

        if (!$subscription || $subscription->getRecruiter() !== $this->getUser()) {
            throw $this->createNotFoundException('Abonnement non trouvé');
        }

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
}
