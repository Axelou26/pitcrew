<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\JobOfferRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/favorites')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FavoriteController extends AbstractController
{
    #[Route('/', name: 'app_favorites_index')]
    public function index(FavoriteRepository $favoriteRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($user->isRecruiter()) {
            $favoriteCandidates = $favoriteRepository->findFavoriteCandidates($user);
            return $this->render('favorite/recruiter_favorites.html.twig', [
                'favorites' => $favoriteCandidates,
            ]);
        } else {
            $favoriteJobOffers = $favoriteRepository->findFavoriteJobOffers($user);
            return $this->render('favorite/candidate_favorites.html.twig', [
                'favorites' => $favoriteJobOffers,
            ]);
        }
    }

    #[Route('/job-offer/{id}/toggle', name: 'app_favorites_toggle_job_offer')]
    #[IsGranted('ROLE_POSTULANT')]
    public function toggleJobOfferFavorite(
        JobOffer $jobOffer, 
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'offre est déjà en favoris
        $isAlreadyFavorite = $favoriteRepository->isJobOfferFavorite($user, $jobOffer);
        
        if ($isAlreadyFavorite) {
            // Supprimer des favoris
            $favorite = $favoriteRepository->findOneBy([
                'user' => $user,
                'jobOffer' => $jobOffer,
                'type' => Favorite::TYPE_JOB_OFFER
            ]);
            
            if ($favorite) {
                $entityManager->remove($favorite);
                $entityManager->flush();
            }
            
            $message = 'Offre retirée des favoris';
            $isFavorite = false;
        } else {
            // Ajouter aux favoris
            $favorite = new Favorite();
            $favorite->setUser($user)
                ->setJobOffer($jobOffer)
                ->setType(Favorite::TYPE_JOB_OFFER);
                
            $entityManager->persist($favorite);
            $entityManager->flush();
            
            $message = 'Offre ajoutée aux favoris';
            $isFavorite = true;
        }
        
        // Si c'est une requête AJAX
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'isFavorite' => $isFavorite
            ]);
        }
        
        // Redirection normale
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
    }

    #[Route('/candidate/{id}/toggle', name: 'app_favorites_toggle_candidate')]
    #[IsGranted('ROLE_RECRUTEUR')]
    public function toggleCandidateFavorite(
        $id, 
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer le candidat par son ID
        $userRepository = $entityManager->getRepository(User::class);
        $candidate = $userRepository->find($id);
        
        // Vérifier si l'utilisateur existe
        if (!$candidate) {
            $this->addFlash('error', 'Le candidat n\'existe pas.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_favorites_index')));
        }
        
        // Vérifier que le candidat n'est pas un recruteur
        if ($candidate->isRecruiter()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas ajouter un recruteur en favoris');
        }
        
        // Vérifier si le candidat est déjà en favoris
        $isAlreadyFavorite = $favoriteRepository->isCandidateFavorite($user, $candidate);
        
        if ($isAlreadyFavorite) {
            // Supprimer des favoris
            $favorite = $favoriteRepository->findOneBy([
                'user' => $user,
                'candidate' => $candidate,
                'type' => Favorite::TYPE_CANDIDATE
            ]);
            
            if ($favorite) {
                $entityManager->remove($favorite);
                $entityManager->flush();
            }
            
            $message = 'Candidat retiré des favoris';
            $isFavorite = false;
        } else {
            // Ajouter aux favoris
            $favorite = new Favorite();
            $favorite->setUser($user)
                ->setCandidate($candidate)
                ->setType(Favorite::TYPE_CANDIDATE);
                
            $entityManager->persist($favorite);
            $entityManager->flush();
            
            $message = 'Candidat ajouté aux favoris';
            $isFavorite = true;
        }
        
        // Si c'est une requête AJAX
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'isFavorite' => $isFavorite
            ]);
        }
        
        // Redirection normale
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_user_profile', ['id' => $candidate->getId()]);
    }
} 