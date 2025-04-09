<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use App\Form\Admin\SubscriptionType;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/subscription', name: 'admin_subscription_')]
class SubscriptionAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SubscriptionRepository $subRepo;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionRepository $subRepo
    ) {
        $this->entityManager = $entityManager;
        $this->subRepo = $subRepo;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $subscriptions = $this->subRepo->findAll();

        return $this->render('admin/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $subscription = new Subscription();
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement créé avec succès.');

            return $this->redirectToRoute('admin_subscription_index');
        }

        return $this->render('admin/subscription/new.html.twig', [
            'subscription' => $subscription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Subscription $subscription): Response
    {
        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_subscription_delete', ['id' => $subscription->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('admin/subscription/show.html.twig', [
            'subscription' => $subscription,
            'delete_form' => $deleteForm,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Subscription $subscription): Response
    {
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement mis à jour avec succès.');

            return $this->redirectToRoute('admin_subscription_index');
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_subscription_delete', ['id' => $subscription->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('admin/subscription/edit.html.twig', [
            'subscription' => $subscription,
            'form' => $form,
            'delete_form' => $deleteForm,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Subscription $subscription): Response
    {
        if ($this->isCsrfTokenValid('delete' . $subscription->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($subscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_subscription_index');
    }
}
