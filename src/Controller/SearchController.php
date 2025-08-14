<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/search')]
class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $query = (string) $request->query->get('q', '');
        $users = [];

        if ($query) {
            $users = $userRepository->searchUsers($query, $this->getUser());
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'users' => $users,
        ]);
    }
}
