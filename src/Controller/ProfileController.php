<?php

namespace App\Controller;

use App\Form\ProfilePostulantType;
use App\Form\ProfileRecruiterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile_index')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Choisir le formulaire en fonction du type d'utilisateur
        if ($user->isRecruiter()) {
            $form = $this->createForm(ProfileRecruiterType::class, $user);
        } else {
            $form = $this->createForm(ProfilePostulantType::class, $user);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la photo de profil
            $profilePicture = $form->get('profilePictureFile')->getData();
            if ($profilePicture) {
                $newFilename = uniqid().'.'.$profilePicture->guessExtension();
                $profilePicture->move(
                    $this->getParameter('profile_pictures_directory'),
                    $newFilename
                );
                $user->setProfilePicture($newFilename);
            }

            // Pour les postulants, gérer l'upload du CV
            if ($user->isPostulant()) {
                $cvFile = $form->get('cvFile')->getData();
                if ($cvFile) {
                    $newFilename = uniqid().'.'.$cvFile->guessExtension();
                    $cvFile->move(
                        $this->getParameter('cv_directory'),
                        $newFilename
                    );
                    $user->setCv($newFilename);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');

            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/documents', name: 'app_profile_documents')]
    #[IsGranted('ROLE_POSTULANT')]
    public function documents(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Gérer l'upload de documents
        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('document');
            if ($uploadedFile) {
                $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                $uploadedFile->move(
                    $this->getParameter('documents_directory'),
                    $newFilename
                );

                $documents = $user->getDocuments() ?? [];
                $documents[] = [
                    'filename' => $newFilename,
                    'originalName' => $uploadedFile->getClientOriginalName(),
                    'uploadedAt' => new \DateTime(),
                ];
                $user->setDocuments($documents);

                $entityManager->flush();
                $this->addFlash('success', 'Le document a été ajouté avec succès !');

                return $this->redirectToRoute('app_profile_documents');
            }
        }

        return $this->render('profile/documents.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/documents/{filename}/delete', name: 'app_profile_documents_delete')]
    #[IsGranted('ROLE_POSTULANT')]
    public function deleteDocument(string $filename, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $documents = $user->getDocuments() ?? [];

        foreach ($documents as $key => $document) {
            if ($document['filename'] === $filename) {
                // Supprimer le fichier physique
                $filePath = $this->getParameter('documents_directory').'/'.$filename;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                // Supprimer l'entrée de la base de données
                unset($documents[$key]);
                $user->setDocuments(array_values($documents));
                $entityManager->flush();

                $this->addFlash('success', 'Le document a été supprimé avec succès !');
                break;
            }
        }

        return $this->redirectToRoute('app_profile_documents');
    }
    
    /**
     * Raccourci pour accéder à l'édition des compétences depuis le profil
     */
    #[Route('/edit-skills', name: 'app_profile_edit_skills')]
    #[IsGranted('ROLE_POSTULANT')]
    public function editSkills(): Response
    {
        return $this->redirectToRoute('app_applicant_edit_skills');
    }
    
    /**
     * Raccourci pour accéder à l'édition de l'expérience depuis le profil
     */
    #[Route('/edit-experience', name: 'app_profile_edit_experience')]
    #[IsGranted('ROLE_POSTULANT')]
    public function editExperience(): Response
    {
        return $this->redirectToRoute('app_applicant_edit_experience');
    }
} 