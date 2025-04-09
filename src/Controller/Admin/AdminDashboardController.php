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
    private RecruiterSubscriptionRepository $recruiterSubRepo;

    public function __construct(
        UserRepository $userRepository,
        JobOfferRepository $jobOfferRepository,
        RecruiterSubscriptionRepository $recruiterSubRepo
    ) {
        $this->userRepository = $userRepository;
        $this->jobOfferRepository = $jobOfferRepository;
        $this->recruiterSubRepo = $recruiterSubRepo;
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $usersCount = $this->userRepository->count([]);
        $jobOffersCount = $this->jobOfferRepository->count([]);
        $activeSubCount = $this->recruiterSubRepo->count(['isActive' => true]);

        return $this->render('admin/dashboard.html.twig', [
            'usersCount' => $usersCount,
            'jobOffersCount' => $jobOffersCount,
            'activeSubscriptionsCount' => $activeSubCount,
        ]);
    }
}
