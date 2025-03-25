<?php

namespace App\Controller\Admin;

use App\Repository\JobOfferRepository;
use App\Repository\RecruiterSubscriptionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/dashboard', name: 'admin_dashboard_')]
class AdminDashboardController extends AbstractController
{
    private UserRepository $userRepository;
    private JobOfferRepository $jobOfferRepository;
    private RecruiterSubscriptionRepository $recruiterSubscriptionRepository;

    public function __construct(
        UserRepository $userRepository,
        JobOfferRepository $jobOfferRepository,
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository
    ) {
        $this->userRepository = $userRepository;
        $this->jobOfferRepository = $jobOfferRepository;
        $this->recruiterSubscriptionRepository = $recruiterSubscriptionRepository;
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $usersCount = $this->userRepository->count([]);
        $jobOffersCount = $this->jobOfferRepository->count([]);
        $activeSubscriptionsCount = $this->recruiterSubscriptionRepository->count(['isActive' => true]);

        return $this->render('admin/dashboard.html.twig', [
            'usersCount' => $usersCount,
            'jobOffersCount' => $jobOffersCount,
            'activeSubscriptionsCount' => $activeSubscriptionsCount,
        ]);
    }
} 