<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\JobOffer;
use App\Repository\JobOfferRepository;
use App\Repository\JobApplicationRepository;

#[Route('/dashboard')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(
        JobOfferRepository $jobOfferRepository,
        JobApplicationRepository $jobApplicationRepository
    ): Response {
        // Pour les recruteurs
        if ($this->isGranted('ROLE_RECRUTEUR')) {
            // Récupérer uniquement les offres du recruteur connecté
            $offers = $jobOfferRepository->findByRecruiter($this->getUser());
            $applications = $jobApplicationRepository->findByRecruiter($this->getUser());

            return $this->render('dashboard/recruiter.html.twig', [
                'offers' => $offers,
                'applications' => $applications,
            ]);
        }
        
        // Pour les postulants
        if ($this->isGranted('ROLE_POSTULANT')) {
            $applications = $jobApplicationRepository->findBy(['applicant' => $this->getUser()]);
            
            return $this->render('dashboard/applicant.html.twig', [
                'applications' => $applications,
            ]);
        }

        // Redirection par défaut
        return $this->redirectToRoute('app_home');
    }

    #[Route('/posts', name: 'app_dashboard_posts')]
    public function posts(PostRepository $postRepository): Response
    {
        return $this->render('dashboard/posts.html.twig', [
            'posts' => $postRepository->findBy(['author' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/applications', name: 'app_dashboard_applications')]
    public function applications(): Response
    {
        return $this->render('dashboard/applications.html.twig', [
            'applications' => [],
        ]);
    }

    #[Route('/offers', name: 'app_dashboard_offers')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function offers(JobOfferRepository $jobOfferRepository): Response
    {
        // Récupérer seulement les offres du recruteur connecté
        $offers = $jobOfferRepository->findByRecruiter($this->getUser());
        
        return $this->render('dashboard/offers.html.twig', [
            'offers' => $offers,
        ]);
    }
} 