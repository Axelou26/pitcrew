<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\RecruiterSubscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Event;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StripeWebhookHandler
{
    private string $webhookSecret;
    private StripeClient $stripe;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RecruiterSubscriptionRepository $recruiterSubRepo,
        private readonly StripeService $stripeService,
        private readonly EmailService $emailService,
        private readonly UserRepository $userRepository,
        ParameterBagInterface $params,
        string $webhookSecret,
        StripeClient $stripe
    ) {
        $this->webhookSecret = $webhookSecret;
        $this->stripe = $stripe;
    }

    public function constructEvent(Request $request): Event
    {
        $payload = $request->getContent();
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

    private function handleCheckoutSessionCompleted(array $data): void
    {
        $this->stripeService->handlePaymentSucceeded($data);
    }

    private function handleInvoicePaymentSucceeded(array $data): void
    {
        $invoice = $data['object'];
        $subId = $invoice['subscription'];
        $customerId = $invoice['customer'];
        
        $user = $this->findUserByStripeId($customerId);
        if (!$user) {
            return;
        }

        $sub = $this->recruiterSubRepo->findOneBy(['stripeSubscriptionId' => $subId]);
        if (!$sub) {
            return;
        }

        $newEndDate = clone $sub->getEndDate();
        $newEndDate->modify('+' . $sub->getSubscription()->getDuration() . ' days');
        $sub->setEndDate($newEndDate)
            ->setIsActive(true)
            ->setCancelled(false);

        $this->entityManager->persist($sub);
        $this->entityManager->flush();

        $this->emailService->sendSubscriptionConfirmation($user, $sub);
    }

    private function handleCustomerSubscriptionDeleted(array $data): void
    {
        $stripeSub = $data['object'];
        $subId = $stripeSub['id'];
        
        $sub = $this->recruiterSubRepo->findOneBy(['stripeSubscriptionId' => $subId]);
        if (!$sub) {
            return;
        }

        $sub->setCancelled(true)
            ->setAutoRenew(false);

        $this->entityManager->persist($sub);
        $this->entityManager->flush();

        $this->emailService->sendSubscriptionCancellationConfirmation($sub->getRecruiter(), $sub);
    }

    private function handlePaymentIntentPaymentFailed(array $data): void
    {
        $paymentIntent = $data['object'];
        $customerId = $paymentIntent['customer'];
        
        $user = $this->findUserByStripeId($customerId);
        if ($user) {
            $this->emailService->sendPaymentFailedNotification($user);
        }
    }

    private function findUserByStripeId(string $stripeCustomerId): ?User
    {
        return $this->userRepository->findOneBy(['stripeCustomerId' => $stripeCustomerId]);
    }
} 