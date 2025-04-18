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
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Rediriger vers la route admin_dashboard_index au lieu de admin_dashboard
        return $this->redirectToRoute('admin_dashboard_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('PitCrew Admin')
            ->setFaviconPath('favicon.ico');
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
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
