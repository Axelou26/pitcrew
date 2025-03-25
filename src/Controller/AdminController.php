<?php

namespace App\Controller;

use App\Repository\JobOfferRepository;
use App\Repository\RecruiterSubscriptionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        JobOfferRepository $jobOfferRepository,
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository
    ): Response
    {
        $usersCount = count($userRepository->findAll());
        $jobOffersCount = count($jobOfferRepository->findAll());
        $activeSubscriptionsCount = count($recruiterSubscriptionRepository->findBy(['isActive' => true]));

        return $this->render('admin/dashboard.html.twig', [
            'users_count' => $usersCount,
            'job_offers_count' => $jobOffersCount,
            'active_subscriptions_count' => $activeSubscriptionsCount,
        ]);
    }
} 