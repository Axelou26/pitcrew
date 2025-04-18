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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\PostService;
use App\Service\PostInteractionService;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PostLike;
use App\Entity\Comment;
use App\Entity\PostReaction;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Entity\PostSearchCriteria;
use App\Entity\User;

#[Route('/post')]
class PostController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;

    public function __construct(
        private PostService $postService,
        private PostInteractionService $interactionService,
        private LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        CacheInterface $cache
    ) {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
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
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            try {
                $this->postService->deletePost($post);
                $this->addFlash('success', 'Le post a été supprimé avec succès !');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la suppression du post (non-AJAX): ' . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de la suppression.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        // Toujours rediriger pour les requêtes non-AJAX
        return $this->redirectToRoute('app_home');
    }

    #[Route('/{id}/like', name: 'app_post_like', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function like(Post $post, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $reactionType = $request->request->get('reactionType');
        if (empty($reactionType) || !array_key_exists($reactionType, PostLike::REACTIONS)) {
            return $this->json([
                'success' => false,
                'message' => 'Type de réaction invalide ou manquant.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $activeReactionType = $this->interactionService->toggleLike($post, $user, $reactionType);

            $cacheKey = 'homepage_data_' . $user->getId();
            $this->cache->delete($cacheKey);

            return $this->json([
                'success' => true,
                'activeReactionType' => $activeReactionType,
                'likesCount' => $post->getLikes()->count()
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du toggle like: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue.'
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

    #[Route('/hashtag/{name}', name: 'app_hashtag_show')]
    public function showHashtag(
        string $name,
        HashtagRepository $hashtagRepository,
        PostRepository $postRepository
    ): Response {
        $hashtag = $hashtagRepository->findOneBy(['name' => $name]);
        if (!$hashtag) {
            throw $this->createNotFoundException('Hashtag non trouvé');
        }

        $posts = $postRepository->findByHashtag($hashtag);
        $trendingHashtags = $hashtagRepository->findTrending(10);

        return $this->render('post/hashtag.html.twig', [
            'hashtag' => $hashtag,
            'posts' => $posts,
            'trendingHashtags' => $trendingHashtags,
        ]);
    }

    #[Route('/hashtags/trending', name: 'app_hashtags_trending')]
    public function trendingHashtags(HashtagRepository $hashtagRepository): Response
    {
        $hashtags = $hashtagRepository->findTrending();

        return $this->render('post/trending_hashtags.html.twig', [
            'hashtags' => $hashtags,
        ]);
    }

    #[Route('/quick-create', name: 'app_post_quick_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function quickCreate(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Récupération et validation du contenu
            $content = $request->request->get('content');
            if (empty($content)) {
                $content = $request->request->get('fullContent');
            }

            if (empty($content)) {
                return new JsonResponse([
                    'error' => 'Le contenu est obligatoire',
                    'field' => 'content'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation de la longueur du contenu
            if (strlen($content) > 5000) {
                return new JsonResponse([
                    'error' => 'Le contenu ne peut pas dépasser 5000 caractères',
                    'field' => 'content'
                ], Response::HTTP_BAD_REQUEST);
            }

            $title = $request->request->get('title');
            if ($title && strlen($title) > 255) {
                return new JsonResponse([
                    'error' => 'Le titre ne peut pas dépasser 255 caractères',
                    'field' => 'title'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation et traitement de l'image
            $imageFile = $request->files->get('imageFile');
            if ($imageFile) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                    return new JsonResponse([
                        'error' => 'Format d\'image non supporté. Utilisez JPG, PNG ou GIF',
                        'field' => 'imageFile'
                    ], Response::HTTP_BAD_REQUEST);
                }

                if ($imageFile->getSize() > 5 * 1024 * 1024) { // 5MB
                    return new JsonResponse([
                        'error' => 'L\'image ne doit pas dépasser 5MB',
                        'field' => 'imageFile'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Création du post avec traitement des hashtags et mentions
            $post = $this->postService->createPost(
                $content,
                $this->getUser(),
                $title ?: null,
                $imageFile
            );

            // Récupération des informations pour la réponse
            $author = $this->getUser();
            $authorPicture = $author->getProfilePicture()
                ? '/uploads/profile_pictures/' . $author->getProfilePicture()
                : null;

            // Extraction des hashtags et mentions pour la réponse
            $hashtags = [];
            foreach ($post->getHashtags() as $hashtag) {
                $hashtags[] = [
                    'name' => $hashtag->getName(),
                    'url' => $this->generateUrl('app_hashtag_show', ['name' => $hashtag->getName()])
                ];
            }

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

            // Construction de la réponse enrichie
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
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du post: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la création de la publication',
                'details' => $this->getParameter('kernel.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/reaction', name: 'app_post_reaction', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function addReaction(Post $post, Request $request): JsonResponse
    {
        try {
            // Récupérer les données JSON
            $data = json_decode($request->getContent(), true);
            $reactionType = $data['type'] ?? null;

            if (empty($reactionType) || !array_key_exists($reactionType, PostLike::REACTIONS)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Type de réaction invalide ou manquant'
                ], Response::HTTP_BAD_REQUEST);
            }

            $activeReactionType = $this->interactionService->toggleLike($post, $this->getUser(), $reactionType);

            // Mettre à jour les compteurs de réactions
            $post->updateReactionCounts();
            $this->entityManager->flush();

            // Définir la carte des réactions
            $reactionMap = [
                'like' => ['emoji' => '👍', 'name' => 'J\'aime', 'class' => 'btn-primary'],
                'congrats' => ['emoji' => '👏', 'name' => 'Bravo', 'class' => 'btn-success'],
                'support' => ['emoji' => '❤️', 'name' => 'Soutien', 'class' => 'btn-danger'],
                'interesting' => ['emoji' => '💡', 'name' => 'Intéressant', 'class' => 'btn-info'],
                'encouraging' => ['emoji' => '💪', 'name' => 'Encouragement', 'class' => 'btn-warning']
            ];
            $defaultReaction = ['emoji' => '👍', 'name' => 'Réagir', 'class' => 'btn-outline-secondary'];

            // Préparer la réponse
            $reactionInfo = $activeReactionType ? $reactionMap[$activeReactionType] : $defaultReaction;
            $reactionInfo['type'] = $activeReactionType;

            return $this->json([
                'success' => true,
                'message' => $activeReactionType ? 'Réaction ajoutée avec succès' : 'Réaction retirée avec succès',
                'reaction' => $reactionInfo,
                'totalReactions' => $post->getLikes()->count(),
                'isActive' => $activeReactionType !== null,
                'reactionCounts' => $post->getReactionCounts()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'ajout de la réaction: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'ajout de la réaction'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

    #[Route('/feed', name: 'app_post_feed', methods: ['GET'])]
    public function feed(): Response
    {
        $posts = $this->postService->findPosts(
            new PostSearchCriteria(
                PostSearchCriteria::TYPE_FEED,
                user: $this->getUser()
            )
        );

        return $this->render('post/_feed.html.twig', [
            'posts' => $posts
        ]);
    }
}
