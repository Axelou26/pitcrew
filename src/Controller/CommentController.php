<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostComment;
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

    /**
     * Rendu HTML de secours pour la liste des commentaires
     */
    private function renderFallbackCommentsList(array $comments): string
    {
        $html = '';

        if (empty($comments)) {
            return '<div class="text-center text-muted p-3">Soyez le premier à commenter.</div>';
        }

        foreach ($comments as $comment) {
            $authorName = $comment->getAuthor() ? $comment->getAuthor()->getFullName() : 'Inconnu';
            $createdAt = $comment->getCreatedAt()->format('d/m/Y H:i');
            $content = htmlspecialchars($comment->getContent());

            $html .= <<<HTML
                <div class="comment mb-3" id="comment-{$comment->getId()}" data-comment-id="{$comment->getId()}">
                    <div class="d-flex">
                        <div class="rounded-circle me-2 bg-light d-flex align-items-center
                         justify-content-center border" 
                        style="width: 32px; height: 32px;">
                            <i class="bi bi-person fs-6 text-muted"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="bg-light rounded-3 p-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">{$authorName}</span>
                                    <small class="text-muted">{$createdAt}</small>
                                </div>
                                <div class="comment-content">{$content}</div>
                            </div>
                        </div>
                    </div>
                </div>
            HTML;
        }

        return $html;
    }

    #[Route('/post/{id}/comments', name: 'app_post_comments', methods: ['GET'])]
    public function listComments(Request $request, int $id): Response
    {
        try {
            $post = $this->getPostOrFail($id);

            if (!$post) {
                return $this->handlePostNotFound($request, $id);
            }

            $comments = $this->commentRepository->findBy(['post' => $post], ['createdAt' => 'ASC']);

            if ($request->isXmlHttpRequest()) {
                return $this->handleAjaxRequest($request, $post, $comments);
            }

            return $this->render('comment/index.html.twig', [
                'post'     => $post,
                'comments' => $comments,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($request, $e);
        }
    }

    private function getPostOrFail(int $id): ?Post
    {
        return $this->entityManager->getRepository(Post::class)->find($id);
    }

    private function handlePostNotFound(Request $request, int $id): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'error' => 'Post non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        throw $this->createNotFoundException('Le post #' . $id . ' n\'a pas été trouvé.');
    }

    private function handleAjaxRequest(Request $request, Post $post, array $comments): Response
    {
        if ($request->headers->has('X-Debug')) {
            return $this->getDebugResponse($post, $comments);
        }

        return $this->getNormalAjaxResponse($post, $comments);
    }

    private function getDebugResponse(Post $post, array $comments): JsonResponse
    {
        $commentsList = [];
        foreach ($comments as $comment) {
            $commentsList[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'author' => $comment->getAuthor() ? $comment->getAuthor()->getFullName() : 'Inconnu'
            ];
        }

        return new JsonResponse([
            'post' => [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'comments_count' => count($comments)
            ],
            'comments' => $commentsList
        ]);
    }

    private function getNormalAjaxResponse(Post $post, array $comments): Response
    {
        try {
            return $this->render('comment/_list.html.twig', [
                'post'     => $post,
                'comments' => $comments,
            ]);
        } catch (\Exception $renderException) {
            return $this->getFallbackResponse($comments, $renderException);
        }
    }

    private function getFallbackResponse(array $comments, \Exception $renderException): JsonResponse
    {
        return new JsonResponse([
            'html' => $this->renderFallbackCommentsList($comments),
            'renderError' => $renderException->getMessage(),
            'details' => 'Le template n\'a pas pu être rendu - affichage de secours.',
        ]);
    }

    private function handleException(Request $request, \Exception $exception): Response
    {
        error_log('Erreur lors du chargement des commentaires: ' . $exception->getMessage()
        . ' - ' . $exception->getTraceAsString());

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors du chargement des commentaires',
                'debug' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        throw $exception;
    }

    #[Route('/post/{id}/comment/add', name: 'app_post_comment_add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addComment(Post $post, Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $content  = $data['content'] ?? '';
        $parentId = $data['parentId'] ?? null;

        if (!$content || trim($content) === '') {
            return $this->json([
                'success' => false,
                'error'   => 'Le commentaire ne peut pas être vide',
            ], 400);
        }

        $parentComment = null;
        if ($parentId) {
            $parentComment = $this->commentRepository->find($parentId);
            if (!$parentComment) {
                return $this->json([
                    'success' => false,
                    'error'   => 'Commentaire parent non trouvé',
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
                    'id'      => $comment->getId(),
                    'content' => $comment->getContent(),
                    'author'  => [
                        'id'             => $comment->getAuthor()->getId(),
                        'fullName'       => $comment->getAuthor()->getFullName(),
                        'profilePicture' => $comment->getAuthor()->getProfilePicture(),
                    ],
                    'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                ],
                'html' => $this->renderView('comment/_comment.html.twig', [
                    'comment' => $comment,
                ]),
                'commentsCount'      => $post->getCommentsCount(),
                'userProfilePicture' => $this->getUser()->getProfilePicture(),
                'userName'           => $this->getUser()->getFullName(),
                'commentId'          => $comment->getId(),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error'   => 'Une erreur est survenue lors de l\'ajout du commentaire',
            ], 500);
        }
    }

    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
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
            'success'       => true,
            'commentsCount' => $post->getCommentsCount(),
        ]);
    }
}
