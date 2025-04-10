<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostComment;
use App\Form\PostCommentType;
use App\Repository\PostCommentRepository;
use App\Service\PostInteractionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentController extends AbstractController
{
    public function __construct(
        private PostInteractionService $interactionService,
        private PostCommentRepository $commentRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/post/{id}/comments', name: 'app_post_comments', methods: ['GET'])]
    public function listComments(Post $post, Request $request): Response
    {
        $comments = $this->commentRepository->findByPost($post);

        if ($request->isXmlHttpRequest()) {
            return $this->render('comment/_list.html.twig', [
                'post' => $post,
                'comments' => $comments,
            ]);
        }

        return $this->render('comment/index.html.twig', [
            'post' => $post,
            'comments' => $comments,
        ]);
    }

    #[Route('/post/{id}/comment/add', name: 'app_post_comment_add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addComment(Post $post, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';
        $parentId = $data['parentId'] ?? null;

        if (!$content || trim($content) === '') {
            return $this->json([
                'success' => false,
                'error' => 'Le commentaire ne peut pas être vide'
            ], 400);
        }

        $parentComment = null;
        if ($parentId) {
            $parentComment = $this->commentRepository->find($parentId);
            if (!$parentComment) {
                return $this->json([
                    'success' => false,
                    'error' => 'Commentaire parent non trouvé'
                ], 404);
            }
        }

        try {
            $comment = $this->interactionService->addComment(
                $post,
                $this->getUser(),
                $content,
                $parentComment
            );

            // Rafraîchir le post pour obtenir le nouveau compte
            $this->entityManager->refresh($post);

            return $this->json([
                'success' => true,
                'comment' => [
                    'id' => $comment->getId(),
                    'content' => $comment->getContent(),
                    'author' => [
                        'id' => $comment->getAuthor()->getId(),
                        'fullName' => $comment->getAuthor()->getFullName()
                    ],
                    'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                    'html' => $this->renderView('comment/_comment.html.twig', [
                        'comment' => $comment
                    ])
                ],
                'commentsCount' => $post->getCommentsCount()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors de l\'ajout du commentaire'
            ], 500);
        }
    }

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(PostComment $comment): JsonResponse
    {
        if (!$this->getUser() || ($this->getUser() !== $comment->getAuthor() && !$this->isGranted('ROLE_ADMIN'))) {
            return $this->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $post = $comment->getPost();
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        // Mettre à jour le compteur de commentaires
        $post->updateCommentsCounter();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'commentsCount' => $post->getCommentsCount()
        ]);
    }
}
