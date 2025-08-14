<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StripeWebhookHandler
{
    private string $webhookSecret;
    private StripeClient $stripe;
    private EntityManagerInterface $entityManager;
    private SubscriptionService $subscriptionService;
    private EmailService $emailService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionService $subscriptionService,
        EmailService $emailService,
        LoggerInterface $logger,
        ParameterBagInterface $params,
        string $webhookSecret,
        StripeClient $stripe
    ) {
        $this->entityManager       = $entityManager;
        $this->subscriptionService = $subscriptionService;
        $this->emailService        = $emailService;
        $this->logger              = $logger;
        $this->webhookSecret       = $webhookSecret;
        $this->stripe              = $stripe;
    }

    public function constructEvent(Request $request): Event
    {
        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        if ($sigHeader === null) {
            throw new BadRequestHttpException('Stripe signature header is missing');
        }

        try {
            return $this->stripe->webhooks->constructEvent(
                $payload,
                $sigHeader,
                $this->webhookSecret
            );
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Invalid payload or signature', $e);
        }
    }

    public function handleEvent(Event $event): void
    {
        $method = 'handle' . str_replace('.', '', ucwords($event->type, '.'));
        if (method_exists($this, $method)) {
            $this->$method($event->data->toArray());
        }
    }

    /**
     * Gère l'événement checkout.session.completed.
     *
     * @param array<string, mixed> $data
     */
    public function handleCheckoutSessionCompleted(array $data): void
    {
        $this->subscriptionService->handlePaymentSucceeded($data);
    }

    /**
     * Gère l'événement invoice.payment_succeeded.
     *
     * @param array<string, mixed> $data
     */
    public function handleInvoicePaymentSucceeded(array $data): void
    {
        $subscription = $this->subscriptionService->findByStripeSubscriptionId($data['subscription'] ?? '');

        if ($subscription === null) {
            $this->logger->warning(
                'Subscription not found for Stripe subscription ID: ' . ($data['subscription'] ?? '')
            );

            return;
        }

        $recruiter = $subscription->getRecruiter();
        if ($recruiter === null) {
            $this->logger->warning('Recruiter not found for subscription: ' . $subscription->getId());

            return;
        }

        // Mettre à jour la date de fin de l'abonnement
        $startDate = $subscription->getStartDate();
        if ($startDate === null) {
            $this->logger->warning('Subscription start date is null for subscription: ' . $subscription->getId());

            return;
        }

        $duration = $subscription->getDuration();
        if ($duration === null) {
            $this->logger->warning('Subscription duration is null for subscription: ' . $subscription->getId());

            return;
        }

        $endDate = clone $startDate;
        $endDate->modify('+' . $duration . ' months');

        $recruiter->setEndDate($endDate);
        $this->entityManager->flush();
    }

    /**
     * Gère l'événement customer.subscription.deleted.
     *
     * @param array<string, mixed> $data
     */
    public function handleCustomerSubscriptionDeleted(array $data): void
    {
        $subscription = $this->subscriptionService->findByStripeSubscriptionId($data['id'] ?? '');

        if ($subscription === null) {
            $this->logger->warning('Subscription not found for Stripe subscription ID: ' . ($data['id'] ?? ''));

            return;
        }

        $recruiter = $subscription->getRecruiter();
        if ($recruiter === null) {
            $this->logger->warning('Recruiter not found for subscription: ' . $subscription->getId());

            return;
        }

        // Désactiver l'abonnement
        $recruiter->setIsActive(false);
        $this->entityManager->flush();

        // Envoyer un email de confirmation
        $this->emailService->sendSubscriptionCancellationConfirmation($recruiter, 'Votre abonnement a été annulé');
    }

    /**
     * Gère l'événement payment_intent.payment_failed.
     *
     * @param array<string, mixed> $data
     */
    public function handlePaymentIntentPaymentFailed(array $data): void
    {
        $this->emailService->sendPaymentFailedNotification($data['customer'] ?? '', $data['amount'] ?? 0);
    }

    private function findUserByStripeId(string $stripeCustomerId): ?User
    {
        return $this->userRepository->findOneBy(['stripeCustomerId' => $stripeCustomerId]);
    }
}
