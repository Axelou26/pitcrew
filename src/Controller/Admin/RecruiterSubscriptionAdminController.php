<?php

namespace App\Controller\Admin;

use App\Entity\RecruiterSubscription;
use App\Form\Admin\RecruiterSubscriptionType;
use App\Repository\RecruiterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/recruiter-subscription', name: 'admin_recruiter_subscription_')]
class RecruiterSubscriptionAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RecruiterSubscriptionRepository $recruiterSubscriptionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RecruiterSubscriptionRepository $recruiterSubscriptionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->recruiterSubscriptionRepository = $recruiterSubscriptionRepository;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $recruiterSubscriptions = $this->recruiterSubscriptionRepository->findAll();

        return $this->render('admin/recruiter_subscription/index.html.twig', [
            'recruiter_subscriptions' => $recruiterSubscriptions,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $recruiterSubscription = new RecruiterSubscription();
        $form = $this->createForm(RecruiterSubscriptionType::class, $recruiterSubscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($recruiterSubscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement recruteur créé avec succès.');

            return $this->redirectToRoute('admin_recruiter_subscription_index');
        }

        return $this->render('admin/recruiter_subscription/new.html.twig', [
            'recruiter_subscription' => $recruiterSubscription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(RecruiterSubscription $recruiterSubscription): Response
    {
        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_recruiter_subscription_delete', ['id' => $recruiterSubscription->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('admin/recruiter_subscription/show.html.twig', [
            'recruiter_subscription' => $recruiterSubscription,
            'delete_form' => $deleteForm,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RecruiterSubscription $recruiterSubscription): Response
    {
        $form = $this->createForm(RecruiterSubscriptionType::class, $recruiterSubscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement recruteur mis à jour avec succès.');

            return $this->redirectToRoute('admin_recruiter_subscription_index');
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_recruiter_subscription_delete', ['id' => $recruiterSubscription->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('admin/recruiter_subscription/edit.html.twig', [
            'recruiter_subscription' => $recruiterSubscription,
            'form' => $form,
            'delete_form' => $deleteForm,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, RecruiterSubscription $recruiterSubscription): Response
    {
        if ($this->isCsrfTokenValid('delete' . $recruiterSubscription->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($recruiterSubscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonnement recruteur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_recruiter_subscription_index');
    }
}
