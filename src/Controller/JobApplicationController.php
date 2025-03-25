<?php

namespace App\Controller;

use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Form\JobApplicationType;
use App\Repository\JobApplicationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/applications')]
class JobApplicationController extends AbstractController
{
    #[Route('/', name: 'app_job_application_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(JobApplicationRepository $jobApplicationRepository): Response
    {
        // Si l'utilisateur est un postulant, on affiche ses candidatures
        if ($this->isGranted('ROLE_POSTULANT')) {
            return $this->render('job_application/index.html.twig', [
                'applications' => $jobApplicationRepository->findBy(['applicant' => $this->getUser()]),
            ]);
        }
        // Si l'utilisateur est un recruteur, on le redirige vers la vue des candidatures reçues
        elseif ($this->isGranted('ROLE_RECRUTEUR')) {
            return $this->redirectToRoute('app_job_application_recruiter');
        }
        
        // Si l'utilisateur n'a ni le rôle postulant ni recruteur, on lui refuse l'accès
        throw $this->createAccessDeniedException('Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
    }

    #[Route('/recruiter', name: 'app_job_application_recruiter', methods: ['GET'])]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function recruiterApplications(JobApplicationRepository $jobApplicationRepository): Response
    {
        // Récupérer les candidatures pour les offres du recruteur
        $applications = $jobApplicationRepository->findByRecruiter($this->getUser());
        
        return $this->render('job_application/recruiter_index.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/new/{id}', name: 'app_job_application_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_POSTULANT')]
    public function new(
        Request $request,
        JobOffer $jobOffer,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        NotificationService $notificationService
    ): Response {
        // Vérifier si l'utilisateur a déjà postulé
        $existingApplication = $entityManager->getRepository(JobApplication::class)->findOneBy([
            'applicant' => $this->getUser(),
            'jobOffer' => $jobOffer,
        ]);

        if ($existingApplication) {
            $this->addFlash('warning', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        $application = new JobApplication();
        $application->setApplicant($this->getUser());
        $application->setJobOffer($jobOffer);
        
        $form = $this->createForm(JobApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du CV
            $resumeFile = $form->get('resume')->getData();
            
            // Vérification que le CV est bien présent (obligatoire)
            if (!$resumeFile) {
                $this->addFlash('error', 'Le CV est obligatoire.');
                return $this->redirectToRoute('app_job_application_new', ['id' => $jobOffer->getId()]);
            }

            try {
                $uploadDir = $this->getParameter('resumes_directory');
                
                // Créer le répertoire s'il n'existe pas
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Générer un nom de fichier unique
                $originalFilename = pathinfo($resumeFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$resumeFile->guessExtension();

                // Déplacer le fichier
                $resumeFile->move($uploadDir, $newFilename);
                
                // Sauvegarder le nom du fichier dans l'entité
                $application->setResume($newFilename);
                
                // Initialiser les champs S3 avec des valeurs nulles (on n'utilise plus S3)
                $application->setResumeS3Key(null);
                $application->setResumeUrl(null);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de votre CV : ' . $e->getMessage());
                return $this->redirectToRoute('app_job_application_new', ['id' => $jobOffer->getId()]);
            }

            // Gestion des documents additionnels
            $additionalFiles = $form->get('additionalDocuments')->getData();
            if ($additionalFiles) {
                $documentsDirectory = $this->getParameter('documents_directory');
                
                // Créer le répertoire de documents s'il n'existe pas
                if (!is_dir($documentsDirectory)) {
                    mkdir($documentsDirectory, 0777, true);
                }
                
                $uploadedDocuments = [];
                
                foreach ($additionalFiles as $file) {
                    try {
                        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                        $file->move($documentsDirectory, $newFilename);
                        
                        // Stocker le nom original et le nouveau nom du fichier
                        $uploadedDocuments[] = [
                            'original_name' => $file->getClientOriginalName(),
                            'file_name' => $newFilename,
                            'mime_type' => $file->getMimeType(),
                            'size' => $file->getSize()
                        ];
                        
                        $application->addDocument($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('warning', 'Un des documents n\'a pas pu être téléchargé : ' . $e->getMessage());
                        continue;
                    }
                }
                
                // Initialiser les champs S3 avec des valeurs vides (on n'utilise plus S3)
                $application->setDocumentsS3Keys([]);
                $application->setDocumentsUrls([]);
            } else {
                // Initialiser les champs S3 avec des valeurs vides (on n'utilise plus S3)
                $application->setDocumentsS3Keys([]);
                $application->setDocumentsUrls([]);
            }

            $entityManager->persist($application);
            
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Votre candidature a été envoyée avec succès !');
                
                // Envoyer une notification au recruteur
                $notificationService->notifyNewApplication($application);
                
                return $this->redirectToRoute('app_job_offer_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement de votre candidature : ' . $e->getMessage());
                return $this->redirectToRoute('app_job_application_new', ['id' => $jobOffer->getId()]);
            }
        }

        return $this->render('job_application/new.html.twig', [
            'form' => $form->createView(),
            'offer' => $jobOffer,
        ]);
    }

    #[Route('/{id}', name: 'app_job_application_show', methods: ['GET'])]
    public function show(JobApplication $application): Response
    {
        // Vérifier que l'utilisateur est soit le candidat soit le recruteur
        if ($application->getApplicant() !== $this->getUser() && 
            $application->getJobOffer()->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier si les fichiers existent physiquement
        $resumeExists = file_exists($this->getParameter('resumes_directory') . '/' . $application->getResume());
        
        $documentExists = [];
        foreach ($application->getDocuments() as $document) {
            $documentExists[$document] = file_exists($this->getParameter('documents_directory') . '/' . $document);
        }

        return $this->render('job_application/show.html.twig', [
            'application' => $application,
            'resumeExists' => $resumeExists,
            'documentExists' => $documentExists
        ]);
    }

    #[Route('/{id}/download/resume', name: 'app_job_application_download_resume', methods: ['GET'])]
    public function downloadResume(JobApplication $application): Response
    {
        // Vérifier que l'utilisateur est soit le candidat soit le recruteur
        if ($application->getApplicant() !== $this->getUser() && 
            $application->getJobOffer()->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $resumePath = $this->getParameter('resumes_directory') . '/' . $application->getResume();
        
        if (!file_exists($resumePath)) {
            throw $this->createNotFoundException('Le CV demandé n\'existe pas.');
        }

        return $this->file($resumePath);
    }

    #[Route('/{id}/download/document/{document}', name: 'app_job_application_download_document', methods: ['GET'])]
    public function downloadDocument(JobApplication $application, string $document): Response
    {
        // Vérifier que l'utilisateur est soit le candidat soit le recruteur
        if ($application->getApplicant() !== $this->getUser() && 
            $application->getJobOffer()->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier que le document appartient bien à cette candidature
        if (!in_array($document, $application->getDocuments())) {
            throw $this->createNotFoundException('Le document demandé n\'appartient pas à cette candidature.');
        }

        $documentPath = $this->getParameter('documents_directory') . '/' . $document;
        
        if (!file_exists($documentPath)) {
            throw $this->createNotFoundException('Le document demandé n\'existe pas.');
        }

        return $this->file($documentPath);
    }

    #[Route('/{id}/status', name: 'app_job_application_status', methods: ['POST'])]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function updateStatus(
        Request $request,
        JobApplication $application,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
        // Vérifier que l'utilisateur est le recruteur de l'offre
        if ($application->getJobOffer()->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $status = $request->request->get('status');
        if (in_array($status, ['pending', 'accepted', 'rejected', 'interview'])) {
            $application->setStatus($status);
            $entityManager->flush();

            // Envoyer une notification au candidat
            $notificationService->notifyApplicationStatusChange($application);

            $this->addFlash('success', 'Le statut de la candidature a été mis à jour.');
        }

        return $this->redirectToRoute('app_job_application_show', ['id' => $application->getId()]);
    }
} 