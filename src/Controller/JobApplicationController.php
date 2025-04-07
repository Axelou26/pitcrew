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

#[Route('/job-application')]
class JobApplicationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JobApplicationRepository $jobApplicationRepository,
        private NotificationService $notificationService,
        private EmailService $emailService
    ) {
    }

    #[Route('/', name: 'app_job_application_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(JobApplicationRepository $jobApplicationRepository): Response
    {
        if ($this->isGranted('ROLE_POSTULANT')) {
            return $this->render('job_application/index.html.twig', [
                'applications' => $jobApplicationRepository->findBy([
                    'applicant' => $this->getUser()
                ]),
            ]);
        }

        if ($this->isGranted('ROLE_RECRUTEUR')) {
            return $this->redirectToRoute('app_job_application_recruiter');
        }

        throw $this->createAccessDeniedException(
            'Vous n\'avez pas les droits nécessaires pour accéder à cette page.'
        );
    }

    #[Route('/recruiter', name: 'app_job_application_recruiter', methods: ['GET'])]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function recruiterApplications(
        JobApplicationRepository $jobApplicationRepository
    ): Response {
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
        $existingApplication = $entityManager
            ->getRepository(JobApplication::class)
            ->findOneBy([
                'applicant' => $this->getUser(),
                'jobOffer' => $jobOffer,
            ]);

        if ($existingApplication) {
            $this->addFlash('warning', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute(
                'app_job_offer_show',
                ['id' => $jobOffer->getId()]
            );
        }

        $application = new JobApplication();
        $application->setApplicant($this->getUser());
        $application->setJobOffer($jobOffer);

        $form = $this->createForm(JobApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resumeFile = $form->get('resume')->getData();

            if (!$resumeFile) {
                $this->addFlash('error', 'Le CV est obligatoire.');
                return $this->redirectToRoute(
                    'app_job_application_new',
                    ['id' => $jobOffer->getId()]
                );
            }

            try {
                $uploadDir = $this->getParameter('resumes_directory');

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $originalFilename = pathinfo(
                    $resumeFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = sprintf(
                    '%s-%s.%s',
                    $safeFilename,
                    uniqid(),
                    $resumeFile->guessExtension()
                );

                $resumeFile->move($uploadDir, $newFilename);
                $application->setResume($newFilename);
                $application->setResumeS3Key(null);
                $application->setResumeUrl(null);
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Une erreur est survenue lors du téléchargement de votre CV : ' .
                    $e->getMessage()
                );
                return $this->redirectToRoute(
                    'app_job_application_new',
                    ['id' => $jobOffer->getId()]
                );
            }

            // Gestion des documents additionnels
            $additionalFiles = $form->get('additionalDocuments')->getData();
            if ($additionalFiles) {
                $documentsDirectory = $this->getParameter('documents_directory');

                if (!is_dir($documentsDirectory)) {
                    mkdir($documentsDirectory, 0777, true);
                }

                $uploadedDocuments = [];

                foreach ($additionalFiles as $file) {
                    try {
                        $originalFilename = pathinfo(
                            $file->getClientOriginalName(),
                            PATHINFO_FILENAME
                        );
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = sprintf(
                            '%s-%s.%s',
                            $safeFilename,
                            uniqid(),
                            $file->guessExtension()
                        );

                        $file->move($documentsDirectory, $newFilename);

                        $uploadedDocuments[] = [
                            'original_name' => $file->getClientOriginalName(),
                            'file_name' => $newFilename,
                            'mime_type' => $file->getMimeType(),
                            'size' => $file->getSize()
                        ];

                        $application->addDocument($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash(
                            'warning',
                            'Un des documents n\'a pas pu être téléchargé : ' . $e->getMessage()
                        );
                        continue;
                    }
                }

                $application->setDocumentsS3Keys([]);
                $application->setDocumentsUrls([]);
            } else {
                $application->setDocumentsS3Keys([]);
                $application->setDocumentsUrls([]);
            }

            $entityManager->persist($application);

            try {
                $entityManager->flush();
                $this->addFlash(
                    'success',
                    'Votre candidature a été envoyée avec succès !'
                );

                $notificationService->notifyNewApplication($application);

                return $this->redirectToRoute('app_job_offer_index');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Une erreur est survenue lors de l\'enregistrement de votre candidature : ' .
                    $e->getMessage()
                );
                return $this->redirectToRoute(
                    'app_job_application_new',
                    ['id' => $jobOffer->getId()]
                );
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
        $isApplicant = $application->getApplicant() === $this->getUser();
        $isRecruiter = $application->getJobOffer()->getRecruiter() === $this->getUser();

        if (!$isApplicant && !$isRecruiter) {
            throw $this->createAccessDeniedException();
        }

        $resumePath = $this->getParameter('resumes_directory') . '/' . $application->getResume();
        $resumeExists = file_exists($resumePath);

        $documentExists = [];
        foreach ($application->getDocuments() as $document) {
            $documentPath = $this->getParameter('documents_directory') . '/' . $document;
            $documentExists[$document] = file_exists($documentPath);
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
        $isApplicant = $application->getApplicant() === $this->getUser();
        $isRecruiter = $application->getJobOffer()->getRecruiter() === $this->getUser();

        if (!$isApplicant && !$isRecruiter) {
            throw $this->createAccessDeniedException();
        }

        $resumePath = $this->getParameter('resumes_directory') . '/' . $application->getResume();

        if (!file_exists($resumePath)) {
            throw $this->createNotFoundException('Le CV demandé n\'existe pas.');
        }

        return $this->file($resumePath);
    }

    #[Route('/{id}/download/document/{document}', name: 'app_job_application_download_document', methods: ['GET'])]
    public function downloadDocument(
        JobApplication $application,
        string $document
    ): Response {
        $isApplicant = $application->getApplicant() === $this->getUser();
        $isRecruiter = $application->getJobOffer()->getRecruiter() === $this->getUser();

        if (!$isApplicant && !$isRecruiter) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array($document, $application->getDocuments())) {
            throw $this->createNotFoundException(
                'Le document demandé n\'appartient pas à cette candidature.'
            );
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
        if ($application->getJobOffer()->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $status = $request->request->get('status');
        $validStatuses = ['pending', 'accepted', 'rejected', 'interview'];

        if (in_array($status, $validStatuses)) {
            $application->setStatus($status);
            $entityManager->flush();

            $notificationService->notifyApplicationStatusChange($application);
            $this->addFlash('success', 'Le statut de la candidature a été mis à jour.');
        }

        return $this->redirectToRoute(
            'app_job_application_show',
            ['id' => $application->getId()]
        );
    }

    public function apply(
        Request $request,
        JobOffer $jobOffer,
        NotificationService $notificationService
    ): Response {
        // ... existing code ...
    }

    private function handleApplicationSubmission(
        JobApplication $jobApplication,
        JobOffer $jobOffer
    ): void {
        // ... existing code ...
    }
}
