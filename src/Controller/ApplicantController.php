<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\JobOffer;
use App\Form\ApplicationType;
use App\Service\FileUploader;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\ApplicantSkillsType;
use App\Form\ApplicantExperienceType;
use App\Entity\Applicant;
use App\Entity\User;

#[Route('/applicant')]
class ApplicantController extends AbstractController
{
    /**
     * Permet à un utilisateur de modifier ses compétences (techniques et soft skills)
     */
    #[Route('/edit-skills', name: 'app_applicant_edit_skills')]
    public function editSkills(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $form = $this->createForm(ApplicantSkillsType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Vos compétences ont été mises à jour avec succès.');
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('applicant/edit_skills.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/dashboard', name: 'app_applicant_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $this->ensureUserIsApplicant();

        $applicant = $this->getUser();

        // Récupérer les candidatures de l'utilisateur
        $applications = $entityManager->getRepository(Application::class)->findBy([
            'applicant' => $applicant
        ], ['createdAt' => 'DESC']);

        // Récupérer les offres favorites
        $favoriteOffers = $applicant->getFavoriteOffers();

        // Récupérer les dernières offres d'emploi
        $latestOffers = $entityManager->getRepository(JobOffer::class)->findBy(
            ['isActive' => true],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('applicant/dashboard.html.twig', [
            'applications' => $applications,
            'favoriteOffers' => $favoriteOffers,
            'latestOffers' => $latestOffers
        ]);
    }

    #[Route('/job-offers', name: 'app_applicant_job_offers')]
    public function jobOffers(Request $request, EntityManagerInterface $entityManager): Response
    {
        $filters = [
            'location' => $request->query->get('location'),
            'contractType' => $request->query->get('contractType')
        ];

        $search = $request->query->get('search');

        $jobOffers = $entityManager->getRepository(JobOffer::class)
            ->searchOffers($search, $filters);

        // Ajout du search dans les filtres pour le template
        $filters['search'] = $search;

        return $this->render('applicant/job_offers.html.twig', [
            'jobOffers' => $jobOffers,
            'filters' => $filters
        ]);
    }

    #[Route('/job-offer/{id}', name: 'app_applicant_job_offer_show')]
    public function showJobOffer(JobOffer $jobOffer): Response
    {
        return $this->render('applicant/job_offer_show.html.twig', [
            'jobOffer' => $jobOffer
        ]);
    }

    #[Route('/job-offer/{id}/apply', name: 'app_applicant_job_offer_apply')]
    public function apply(
        JobOffer $jobOffer,
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): Response {
        // Vérifier si l'utilisateur n'a pas déjà postulé
        $existingApplication = $entityManager->getRepository(Application::class)->findOneBy([
            'applicant' => $this->getUser(),
            'jobOffer' => $jobOffer
        ]);

        if ($existingApplication) {
            $this->addFlash('error', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('app_applicant_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        $application = new Application();
        $application->setApplicant($this->getUser());
        $application->setJobOffer($jobOffer);
        $application->setStatus('pending');
        $application->setCreatedAt(new DateTime());

        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer le téléchargement du CV
            $cvFile = $form->get('cvFile')->getData();
            if ($cvFile) {
                $cvFilename = $fileUploader->upload($cvFile, 'cv-');
                $application->setCvFilename($cvFilename);
            }

            $entityManager->persist($application);
            $entityManager->flush();

            $this->addFlash('success', 'Votre candidature a été envoyée avec succès.');
            return $this->redirectToRoute('app_applicant_dashboard');
        }

        return $this->render('applicant/apply.html.twig', [
            'form' => $form->createView(),
            'jobOffer' => $jobOffer
        ]);
    }

    #[Route('/job-offer/{id}/toggle-favorite', name: 'app_applicant_toggle_favorite')]
    public function toggleFavorite(JobOffer $jobOffer, EntityManagerInterface $entityManager): Response
    {
        $applicant = $this->getUser();

        $isFavorite = $applicant->getFavoriteOffers()->contains($jobOffer);
        $message = $isFavorite ? 'Offre retirée des favoris.' : 'Offre ajoutée aux favoris.';
        $action = $isFavorite ? 'removeFavoriteOffer' : 'addFavoriteOffer';

        $applicant->$action($jobOffer);

        $entityManager->flush();
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_applicant_job_offer_show', ['id' => $jobOffer->getId()]);
    }

    #[Route('/applications', name: 'app_applicant_applications')]
    public function applications(EntityManagerInterface $entityManager): Response
    {
        $applications = $entityManager->getRepository(Application::class)->findBy(
            ['applicant' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('applicant/applications.html.twig', [
            'applications' => $applications
        ]);
    }

    /**
     * Permet à un candidat de modifier son expérience professionnelle
     */
    #[Route('/edit-experience', name: 'app_applicant_edit_experience')]
    public function editExperience(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ApplicantExperienceType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre expérience a été mise à jour avec succès.');
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('applicant/edit_experience.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
