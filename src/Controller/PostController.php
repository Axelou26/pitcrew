<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostComment;
use App\Form\PostType;
use App\Form\PostCommentType;
use App\Repository\PostRepository;
use App\Repository\PostCommentRepository;
use App\Repository\HashtagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\PostService;
use App\Service\PostInteractionService;
use App\Service\RecommendationService;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PostLike;
use Symfony\Contracts\Cache\CacheInterface;
use App\Service\Post\PostSearchCriteria;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;

#[Route('/post')]
class PostController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;
    private RecommendationService $recommendationService;

    public function __construct(
        private PostService $postService,
        private PostInteractionService $interactionService,
        private LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CacheInterface $cache,
        RecommendationService $recommendationService
    ) {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->recommendationService = $recommendationService;
    }

    #[Route('s', name: 'app_post_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_dashboard_posts');
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function new(Request $request): Response
    {
        $post = new Post();
        $post->setAuthor($this->getUser());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $post = $this->postService->createPost(
                    $post->getContent(),
                    $this->getUser(),
                    $post->getTitle(),
                    $form->get('imageFile')->getData()
                );

                $this->addFlash('success', 'Votre post a été créé avec succès !');
                return $this->redirectToRoute('app_dashboard_posts');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/feed', name: 'app_post_feed', methods: ['GET'])]
    public function feed(Request $request): Response
    {
        try {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = max(1, $request->query->getInt('limit', 10));

            $user = $this->getUser();
            if (!$user) {
                // Si l'utilisateur n'est pas connecté, retourner une erreur ou rediriger
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'error' => 'Vous devez être connecté pour voir le feed',
                        'html' => '<div class="alert alert-warning">Veuillez vous connecter pour voir le feed</div>'
                    ], Response::HTTP_UNAUTHORIZED);
                }

                return $this->redirectToRoute('app_login');
            }

            // 1. Récupérer les posts des amis
            $friendsPosts = $this->postService->findPosts(
                PostSearchCriteria::forFeed($user),
                $page,
                $limit
            );

            // 2. Récupérer les posts recommandés "Pour vous"
            $recommendedPosts = $this->recommendationService->getRecommendedPosts($user, $limit);

            // 3. Fusionner les deux listes
            $allPosts = array_merge($friendsPosts, $recommendedPosts);

            // 4. Supprimer les doublons (posts qui pourraient être à la fois d'amis et recommandés)
            $uniquePosts = [];
            $postIds = [];

            foreach ($allPosts as $post) {
                if (!in_array($post->getId(), $postIds)) {
                    $postIds[] = $post->getId();
                    $uniquePosts[] = $post;
                }
            }

            // 5. Trier par date de création (du plus récent au plus ancien)
            usort($uniquePosts, function ($a, $b) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            });

            // 6. Limiter le nombre de posts à afficher
            $posts = array_slice($uniquePosts, 0, $limit);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'html' => $this->renderView('post/_feed.html.twig', [
                        'posts' => $posts
                    ]),
                    'hasMore' => count($posts) === $limit
                ]);
            }

            return $this->render('post/_feed.html.twig', [
                'posts' => $posts,
                'currentPage' => $page,
                'hasMore' => count($posts) === $limit
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du chargement du feed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $this->getUser()?->getId(),
                'page' => $page ?? 1
            ]);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'error' => 'Une erreur est survenue lors du chargement des publications',
                    'html' => '<div class="alert alert-danger">Erreur lors du chargement des publications</div>'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            throw $e;
        }
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        $comment = new PostComment();
        $comment->setPost($post);

        $commentForm = $this->createForm(PostCommentType::class, $comment);

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm->createView(),
            'comments' => $post->getComments(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('POST_EDIT', 'post')]
    public function edit(Request $request, Post $post): Response
    {
        if ($request->isXmlHttpRequest()) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('edit' . $post->getId(), $token)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token CSRF invalide'
                ], Response::HTTP_BAD_REQUEST);
            }

            try {
                $content = $request->request->get('content');
                $title = $request->request->get('title');
                $imageFile = $request->files->get('image');

                if (empty($content)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Le contenu est obligatoire'
                    ], Response::HTTP_BAD_REQUEST);
                }

                $this->postService->updatePost(
                    $post,
                    $content,
                    $title,
                    $imageFile
                );

                return $this->json([
                    'success' => true,
                    'message' => 'Post modifié avec succès'
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->postService->updatePost(
                    $post,
                    $post->getContent(),
                    $post->getTitle(),
                    $form->get('imageFile')->getData()
                );

                $this->addFlash('success', 'Votre post a été modifié avec succès !');
                return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    private function getFormErrors($form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, PostRepository $postRepository, int $id): Response
    {
        $post = $postRepository->find($id);

        if (!$post) {
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Post déjà supprimé.'
                ]);
            }
            $this->addFlash('info', 'Post déjà supprimé.');
            return $this->redirectToRoute('app_home');
        }

        $this->denyAccessUnlessGranted('POST_DELETE', $post);

        // Pour les requêtes AJAX (attendues par post-interactions.js)
        if ($request->isXmlHttpRequest()) {
            try {
                $this->postService->deletePost($post);
                return $this->json(['success' => true]);
            } catch (\Exception $e) {
                $this->logger->error('Erreur AJAX lors de la suppression du post: ' . $e->getMessage());
                return $this->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // Pour les requêtes non-AJAX (formulaire standard)
        if (!$this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_home');
        }

        try {
            $this->postService->deletePost($post);
            $this->addFlash('success', 'Le post a été supprimé avec succès !');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression du post (non-AJAX): ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la suppression.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/{id}/like', name: 'post_like', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function toggleLike(
        Post $post,
        Request $request
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour liker un post.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $isLiked = $this->interactionService->toggleLike($post, $user);

            // Mettre à jour le compteur de likes
            $post->updateLikesCounter();
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'isLiked' => $isLiked,
                'likesCount' => $post->getLikesCount(),
                'message' => $isLiked ? 'Post liké !' : 'Like retiré !'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du like/unlike: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du like.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/share', name: 'app_post_share', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function share(Post $originalPost, Request $request): JsonResponse
    {
        try {
            // Vérifier si le post est déjà un repost
            if ($originalPost->getOriginalPost() !== null) {
                return $this->json([
                    'success' => false,
                    'message' => 'Un post repartagé ne peut pas être repartagé.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Récupérer le commentaire soit du JSON soit des paramètres de formulaire
            $comment = $request->request->get('share-comment', '');
            if ($request->getContentTypeFormat() === 'json') {
                $data = json_decode($request->getContent(), true);
                $comment = $data['comment'] ?? $comment;
            }

            // Créer le nouveau post
            $sharedPost = new Post(); // Le constructeur initialise createdAt
            $sharedPost->setAuthor($this->getUser());
            $sharedPost->setOriginalPost($originalPost);

            // Définir un titre basé sur le post original
            $originalTitle = $originalPost->getTitle();
            $sharedPost->setTitle($originalTitle ? "Repost: {$originalTitle}" : "Repost");

            // Définir le contenu du post
            $content = trim($comment) ?: $originalPost->getContent();
            $sharedPost->setContent($content);

            // Si le post original a une image, la copier
            if ($originalPost->getImage()) {
                $sharedPost->setImage($originalPost->getImage());
                $sharedPost->setImageName($originalPost->getImageName());
            }

            // Ajouter le post à la collection des reposts du post original
            $originalPost->addRepost($sharedPost);

            $this->entityManager->persist($sharedPost);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Post repartagé avec succès !',
                'postId' => $sharedPost->getId(),
                'sharesCount' => $originalPost->getReposts()->count()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du partage du post: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du partage.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/hashtags/trending', name: 'app_hashtags_trending')]
    public function trendingHashtags(HashtagRepository $hashtagRepository): Response
    {
        $hashtags = $hashtagRepository->findTrending();

        return $this->render('post/trending_hashtags.html.twig', [
            'hashtags' => $hashtags,
        ]);
    }

    #[Route('/hashtag/{name}', name: 'app_hashtag_show')]
    public function showHashtag(
        string $name,
        HashtagRepository $hashtagRepository,
        PostRepository $postRepository
    ): Response {
        $hashtag = $hashtagRepository->findOneBy(['name' => $name]);
        if (!$hashtag) {
            $this->addFlash('warning', sprintf('Le hashtag #%s n\'existe pas.', $name));
            return $this->redirectToRoute('app_hashtags_trending');
        }

        $posts = $postRepository->findByHashtag($hashtag);
        $trendingHashtags = $hashtagRepository->findTrending(10);

        return $this->render('post/hashtag.html.twig', [
            'hashtag' => $hashtag,
            'posts' => $posts,
            'trendingHashtags' => $trendingHashtags,
        ]);
    }

    private function validateContent(?string $content): ?array
    {
        if (empty($content)) {
            return [
                'error' => 'Le contenu est obligatoire',
                'field' => 'content'
            ];
        }

        if (strlen($content) > 5000) {
            return [
                'error' => 'Le contenu ne peut pas dépasser 5000 caractères',
                'field' => 'content'
            ];
        }

        return null;
    }

    private function validateTitle(?string $title): ?array
    {
        if ($title && strlen($title) > 255) {
            return [
                'error' => 'Le titre ne peut pas dépasser 255 caractères',
                'field' => 'title'
            ];
        }

        return null;
    }

    private function validateImage($imageFile): ?array
    {
        if (!$imageFile) {
            return null;
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
            return [
                'error' => 'Format d\'image non supporté. Utilisez JPG, PNG ou GIF',
                'field' => 'imageFile'
            ];
        }

        if ($imageFile->getSize() > 5 * 1024 * 1024) { // 5MB
            return [
                'error' => 'L\'image ne doit pas dépasser 5MB',
                'field' => 'imageFile'
            ];
        }

        return null;
    }

    #[Route('/quick', name: 'app_post_quick', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function quick(Request $request): JsonResponse
    {
        try {
            $title = $request->request->get('title');
            $content = $request->request->get('content');
            $imageFile = $request->files->get('imageFile');

            // Validation du contenu
            if (empty($content)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Le contenu est obligatoire'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Créer le post
            $post = $this->postService->createPost(
                $content,
                $this->getUser(),
                $title,
                $imageFile
            );

            // Récupérer le HTML du post pour l'afficher sans rechargement
            $postHtml = $this->renderView('post/_post_card.html.twig', [
                'post' => $post,
                'showCommentForm' => false,
                'showFullContent' => true,
            ]);

            return $this->json([
                'success' => true,
                'postId' => $post->getId(),
                'message' => 'Post créé avec succès!',
                'postHtml' => $postHtml,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création rapide du post: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $this->getUser()?->getId()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/quick-create', name: 'app_post_quick_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function quickCreate(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Récupération du contenu
            $content = $request->request->get('content');
            if (empty($content)) {
                $content = $request->request->get('fullContent');
            }

            // Validation du contenu
            $contentValidation = $this->validateContent($content);
            if ($contentValidation !== null) {
                return new JsonResponse($contentValidation, Response::HTTP_BAD_REQUEST);
            }

            // Validation du titre
            $title = $request->request->get('title');
            $titleValidation = $this->validateTitle($title);
            if ($titleValidation !== null) {
                return new JsonResponse($titleValidation, Response::HTTP_BAD_REQUEST);
            }

            // Validation de l'image
            $imageFile = $request->files->get('imageFile');
            $imageValidation = $this->validateImage($imageFile);
            if ($imageValidation !== null) {
                return new JsonResponse($imageValidation, Response::HTTP_BAD_REQUEST);
            }

            // Création du post
            $post = $this->postService->createPost(
                $content,
                $this->getUser(),
                $title ?: null,
                $imageFile
            );

            // Préparation de la réponse
            return $this->prepareQuickCreateResponse($post);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du post: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la création de la publication',
                'details' => $this->getParameter('kernel.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function prepareQuickCreateResponse(Post $post): JsonResponse
    {
        $author = $this->getUser();
        $authorPicture = $author->getProfilePicture()
            ? '/uploads/profile_pictures/' . $author->getProfilePicture()
            : null;

        // Extraction des hashtags
        $hashtags = [];
        foreach ($post->getHashtags() as $hashtag) {
            $hashtags[] = [
                'name' => $hashtag->getName(),
                'url' => $this->generateUrl('app_hashtag_show', ['name' => $hashtag->getName()])
            ];
        }

        // Extraction des mentions
        $mentions = [];
        $userRepository = $this->entityManager->getRepository(User::class);
        foreach ($post->getMentions() as $mentionId) {
            $mentionedUser = $userRepository->find($mentionId);
            if ($mentionedUser) {
                $mentions[] = [
                    'fullName' => $mentionedUser->getFullName(),
                    'profileUrl' => $this->generateUrl('app_user_profile', ['userId' => $mentionedUser->getId()])
                ];
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Publication créée avec succès',
            'post' => [
                'id' => $post->getId(),
                'content' => $post->getContent(),
                'title' => $post->getTitle(),
                'image' => $post->getImage(),
                'createdAt' => $post->getCreatedAt()->format('d/m/Y H:i'),
                'author' => [
                    'name' => $author->getFullName(),
                    'picture' => $authorPicture,
                    'id' => $author->getId()
                ],
                'hashtags' => $hashtags,
                'mentions' => $mentions,
                'stats' => [
                    'likes' => 0,
                    'comments' => 0,
                    'shares' => 0
                ]
            ],
            'token' => $this->renderView('csrf_token.html.twig', ['id' => $post->getId()]),
            'html' => $this->renderView('post/_post_card.html.twig', ['post' => $post])
        ], Response::HTTP_CREATED);
    }



    #[Route('/comment/{id}/reply', name: 'app_post_comment_reply', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function reply(PostComment $comment): Response
    {
        $reply = new PostComment();
        $reply->setPost($comment->getPost());
        $reply->setParent($comment);

        $replyForm = $this->createForm(PostCommentType::class, $reply);

        return $this->render('post/reply.html.twig', [
            'comment' => $comment,
            'replyForm' => $replyForm->createView(),
        ]);
    }

    #[Route('/comment/{id}/reply', name: 'app_post_comment_reply_submit', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function submitReply(Request $request, PostComment $comment): Response
    {
        $reply = new PostComment();
        $reply->setPost($comment->getPost());
        $reply->setParent($comment);
        $reply->setAuthor($this->getUser());

        $replyForm = $this->createForm(PostCommentType::class, $reply);
        $replyForm->handleRequest($request);

        if ($replyForm->isSubmitted() && $replyForm->isValid()) {
            $this->entityManager->persist($reply);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre réponse a été publiée avec succès !');
            return $this->redirectToRoute('app_post_show', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render('post/reply.html.twig', [
            'comment' => $comment,
            'replyForm' => $replyForm->createView(),
        ]);
    }

    #[Route('/sync-likes', name: 'app_post_sync_likes', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function syncLikes(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour synchroniser les likes.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $clientLikedPosts = $data['likedPosts'] ?? [];

            // Récupérer les posts réellement likés par l'utilisateur
            $postRepository = $this->entityManager->getRepository(Post::class);
            $serverLikedPosts = [];

            foreach ($clientLikedPosts as $postId) {
                $post = $postRepository->find($postId);
                if ($post && $post->isLikedByUser($user)) {
                    $serverLikedPosts[] = $postId;
                }
            }

            return $this->json([
                'success' => true,
                'serverLikedPosts' => $serverLikedPosts,
                'message' => 'Synchronisation des likes terminée'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la synchronisation des likes: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la synchronisation'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
