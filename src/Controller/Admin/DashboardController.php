<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use App\Entity\RecruiterSubscription;
use App\Entity\User;
use App\Entity\JobOffer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Rediriger vers la route admin_dashboard_index au lieu de admin_dashboard
        return $this->redirectToRoute('admin_dashboard_index');

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('PitCrew Admin')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        yield MenuItem::section('Abonnements');
        yield MenuItem::linkToRoute('Plans d\'abonnement', 'fas fa-tags', 'admin_subscription_index');
        yield MenuItem::linkToRoute('Abonnements recruteurs', 'fas fa-users-cog', 'admin_recruiter_subscription_index');
        
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToRoute('Utilisateurs', 'fas fa-users', 'admin_user_index');
        
        yield MenuItem::section('Offres d\'emploi');
        yield MenuItem::linkToRoute('Offres d\'emploi', 'fas fa-briefcase', 'admin_job_offer_index');
        
        yield MenuItem::section('Site');
        yield MenuItem::linkToRoute('Retour au site', 'fas fa-arrow-left', 'app_home');
    }
} 