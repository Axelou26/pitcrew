<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\JobOffer;
use App\Form\Admin\JobOfferType;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/job-offer', name: 'admin_job_offer_')]
class JobOfferAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private JobOfferRepository $jobOfferRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        JobOfferRepository $jobOfferRepository
    ) {
        $this->entityManager      = $entityManager;
        $this->jobOfferRepository = $jobOfferRepository;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $jobOffers = $this->jobOfferRepository->findAll();

        return $this->render('admin/job_offer/index.html.twig', [
            'job_offers' => $jobOffers,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $jobOffer = new JobOffer();
        $form     = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($jobOffer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Offre d\'emploi créée avec succès.');

            return $this->redirectToRoute('admin_job_offer_index');
        }

        return $this->render('admin/job_offer/new.html.twig', [
            'job_offer' => $jobOffer,
            'form'      => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(JobOffer $jobOffer): Response
    {
        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_job_offer_delete', ['id' => $jobOffer->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('admin/job_offer/show.html.twig', [
            'job_offer'   => $jobOffer,
            'delete_form' => $deleteForm,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JobOffer $jobOffer): Response
    {
        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Offre d\'emploi mise à jour avec succès.');

            return $this->redirectToRoute('admin_job_offer_index');
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_job_offer_delete', ['id' => $jobOffer->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('admin/job_offer/edit.html.twig', [
            'job_offer'   => $jobOffer,
            'form'        => $form,
            'delete_form' => $deleteForm,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, JobOffer $jobOffer): Response
    {
        if ($this->isCsrfTokenValid('delete' . $jobOffer->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($jobOffer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Offre d\'emploi supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_job_offer_index');
    }
}
