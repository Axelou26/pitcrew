<?php

namespace App\Controller;

use App\Entity\JobOffer;
use App\Form\JobOfferType;
use App\Repository\JobOfferRepository;
use App\Repository\FavoriteRepository;
use App\Service\SubscriptionService;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/job-offers')]
class JobOfferController extends AbstractController
{
    private JobOfferRepository $jobOfferRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        JobOfferRepository $jobOfferRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->jobOfferRepository = $jobOfferRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_job_offer_index', methods: ['GET'])]
    public function index(Request $request, JobOfferRepository $jobOfferRepository): Response
    {
        $query = $request->query->get('q');
        $filters = [
            'contractType' => $request->query->get('contractType'),
            'location' => $request->query->get('location'),
            'minSalary' => $request->query->get('minSalary')
        ];

        $offers = $jobOfferRepository->searchOffers($query, $filters);

        // Récupérer les types de contrat distincts pour le filtre
        $contractTypes = $jobOfferRepository->createQueryBuilder('j')
            ->select('DISTINCT j.contractType')
            ->where('j.contractType IS NOT NULL')
            ->getQuery()
            ->getResult();

        // Récupérer les lieux distincts pour le filtre
        $locations = $jobOfferRepository->createQueryBuilder('j')
            ->select('DISTINCT j.location')
            ->where('j.location IS NOT NULL')
            ->getQuery()
            ->getResult();

        return $this->render('job_offer/index.html.twig', [
            'offers' => $offers,
            'contractTypes' => array_column($contractTypes, 'contractType'),
            'locations' => array_column($locations, 'location'),
            'query' => $query,
            'filters' => $filters
        ]);
    }

    // Route sans préfixe pour accéder directement aux offres d'emploi
    #[Route('/job-offers', name: 'app_job_offers', methods: ['GET'])]
    public function jobOffers(Request $request): Response
    {
        // Rediriger vers la route avec préfixe
        return $this->redirectToRoute('app_job_offer_index', $request->query->all());
    }

    #[Route('/new', name: 'app_job_offer_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_RECRUTEUR')]
    #[IsGranted('post_job_offer')]
    public function new(
        Request $request,
        FileUploader $fileUploader,
        SubscriptionService $subscriptionService
    ): Response {
        $jobOffer = new JobOffer();
        $jobOffer->setRecruiter($this->getUser());

        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du logo
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                try {
                    $newLogoFilename = $fileUploader->upload($logoFile, 'logos_directory');
                    $jobOffer->setLogoUrl($newLogoFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            // Décrémenter le nombre d'offres restantes si l'abonnement est limité
            $subscriptionService->decrementRemainingJobOffers($this->getUser());

            $this->entityManager->persist($jobOffer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre offre d\'emploi a été créée avec succès !');
            return $this->redirectToRoute('app_job_offer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('job_offer/new.html.twig', [
            'job_offer' => $jobOffer,
            'form' => $form,
        ]);
    }

    #[Route('/job-offer/{offerId}', name: 'app_job_offer_show')]
    #[Route('/job-offer/by-id/{id}', name: 'app_job_offer_show_by_id')]
    public function show(int $offerId): Response
    {
        $jobOffer = $this->jobOfferRepository->find($offerId);

        if (!$jobOffer) {
            throw $this->createNotFoundException('Offre d\'emploi non trouvée');
        }

        // Récupérer les offres similaires
        $similarOffers = $this->jobOfferRepository->findSimilarOffers($jobOffer);

        return $this->render('job_offer/show.html.twig', [
            'jobOffer' => $jobOffer,
            'similarOffers' => $similarOffers
        ]);
    }

    #[Route('/job-offer/{offerId}/edit', name: 'app_job_offer_edit')]
    public function edit(int $offerId, Request $request): Response
    {
        $jobOffer = $this->jobOfferRepository->find($offerId);

        if (!$jobOffer) {
            throw $this->createNotFoundException('Offre d\'emploi non trouvée');
        }

        // Vérifier que l'utilisateur est bien le propriétaire de l'offre
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette offre');
        }

        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Offre d\'emploi modifiée avec succès');
            return $this->redirectToRoute('app_job_offer_show', ['offerId' => $jobOffer->getId()]);
        }

        return $this->render('job_offer/edit.html.twig', [
            'form' => $form->createView(),
            'jobOffer' => $jobOffer
        ]);
    }

    #[Route('/job-offer/{offerId}/delete', name: 'app_job_offer_delete', methods: ['POST'])]
    public function delete(int $offerId, Request $request): Response
    {
        $jobOffer = $this->jobOfferRepository->find($offerId);

        if (!$jobOffer) {
            throw $this->createNotFoundException('Offre d\'emploi non trouvée');
        }

        // Vérifier que l'utilisateur est bien le propriétaire de l'offre
        if ($jobOffer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette offre');
        }

        if ($this->isCsrfTokenValid('delete' . $offerId, $request->request->get('_token'))) {
            $this->entityManager->remove($jobOffer);
            $this->entityManager->flush();
            $this->addFlash('success', 'Offre d\'emploi supprimée avec succès');
        }

        return $this->redirectToRoute('app_recruiter_dashboard');
    }

    #[Route('/recherche', name: 'app_job_offer_search', methods: ['GET'])]
    public function search(Request $request, JobOfferRepository $jobOfferRepository): Response
    {
        $query = $request->query->get('q');
        $location = $request->query->get('location');
        $contractType = $request->query->get('contract_type');

        $offers = $jobOfferRepository->search($query, $location, $contractType);

        if ($request->isXmlHttpRequest()) {
            return $this->render('job_offer/_offers_list.html.twig', [
                'offers' => $offers,
            ]);
        }

        return $this->render('job_offer/search.html.twig', [
            'offers' => $offers,
            'query' => $query,
            'location' => $location,
            'contract_type' => $contractType,
        ]);
    }
}
