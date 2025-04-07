<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\PostComment;
use App\Entity\PostShare;
use App\Form\PostType;
use App\Form\PostCommentType;
use App\Form\PostShareType;
use App\Repository\PostRepository;
use App\Repository\PostLikeRepository;
use App\Repository\PostCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\UserRepository;
use App\Repository\HashtagRepository;
use App\Service\NotificationService;
use App\Entity\User;
use App\Entity\Hashtag;
use App\Service\ContentProcessorService;
use App\Service\FileUploader;
use Psr\Log\LoggerInterface;

class PostController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private ContentProcessorService $contentProcessor,
        private FileUploader $fileUploader
    ) {
    }

    #[Route('/posts', name: 'app_post_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_dashboard_posts');
    }

    #[Route('/post/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function new(
        Request $request,
        SluggerInterface $slugger
    ): Response {
        $post = new Post();
        $post->setAuthor($this->getUser());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($post->getTitle() === null) {
                $post->setTitle('');
            }

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                try {
                    $newFilename = $this->fileUploader->upload(
                        $imageFile,
                        'posts_directory',
                        $post->getImage()
                    );
                    $post->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            $this->contentProcessor->processPostContent($post, true);
            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre post a été créé avec succès !');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/{id}', name: 'app_post_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Post $post,
        Request $request,
        PostCommentRepository $commentRepository
    ): Response {
        $comment = new PostComment();
        $commentForm = $this->createForm(PostCommentType::class, $comment);

        $page = $request->query->getInt('page', 1);
        $comments = $commentRepository->findByPost($post, $page);

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'comment_form' => $commentForm->createView(),
            'comments' => $comments,
        ]);
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('POST_EDIT', 'post')]
    public function edit(
        Request $request,
        Post $post,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($post->getTitle() === null) {
                $post->setTitle('');
            }

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                try {
                    $newFilename = $this->fileUploader->upload(
                        $imageFile,
                        'posts_directory',
                        $post->getImage()
                    );
                    $post->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            $this->contentProcessor->processPostContent($post, true);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre post a été modifié avec succès !');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('POST_DELETE', 'post')]
    public function delete(
        Request $request,
        Post $post
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le post a été supprimé avec succès !');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/post/create', name: 'app_post_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function create(
        Request $request,
        SluggerInterface $slugger
    ): Response {
        try {
            $this->logger->info('Tentative de création de post rapide', [
                'request_content' => $request->request->all(),
                'has_files' => $request->files->count() > 0
            ]);

            if ($request->request->count() === 0 && $request->files->count() === 0) {
                throw new \Exception('La requête ne contient aucune donnée');
            }

            $content = $request->request->get('content');
            if (!$content || trim($content) === '') {
                throw new \Exception('Le contenu du post ne peut pas être vide');
            }

            $post = new Post();
            $post->setAuthor($this->getUser());

            $title = $request->request->get('title');
            $post->setTitle($title ? trim($title) : '');
            $post->setContent(trim($content));

            $imageFile = $request->files->get('imageFile');
            if ($imageFile) {
                try {
                    $newFilename = $this->fileUploader->upload(
                        $imageFile,
                        'posts_directory'
                    );
                    $post->setImage($newFilename);
                } catch (\Exception $e) {
                    throw new \Exception(
                        'Erreur lors du téléchargement de l\'image : ' . $e->getMessage()
                    );
                }
            }

            $this->contentProcessor->processPostContent($post, true);
            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->notificationService->notifyMentionedUsers($post);

            return $this->json([
                'success' => true,
                'message' => 'Post créé avec succès',
                'post' => [
                    'id' => $post->getId(),
                    'content' => $post->getContent(),
                    'author' => [
                        'id' => $post->getAuthor()->getId(),
                        'username' => $post->getAuthor()->getUsername()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur de création de post: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_content' => $request->request->all(),
                'has_files' => $request->files->count() > 0,
                'request_content_type' => $request->headers->get('Content-Type')
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la création du post: ' . $e->getMessage(),
                'debug_info' => [
                    'request_parameters' => $request->request->count(),
                    'request_files' => $request->files->count(),
                    'content_type' => $request->headers->get('Content-Type')
                ]
            ], 400);
        }
    }

    #[Route('/post/{id}/like', name: 'app_post_like', requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function like(
        Post $post,
        EntityManagerInterface $entityManager,
        PostLikeRepository $likeRepository,
        Request $request,
        NotificationService $notificationService
    ): Response {
        $user = $this->getUser();
        $reactionType = $request->query->get('type', PostLike::REACTION_LIKE);

        // Vérifier si le type de réaction est valide
        if (!array_key_exists($reactionType, PostLike::REACTIONS)) {
            $reactionType = PostLike::REACTION_LIKE;
        }

        // Vérifier si l'utilisateur a déjà liké ce post
        $existingLike = $likeRepository->findOneBy([
            'user' => $user,
            'post' => $post
        ]);

        $clickedSameReaction = false;
        $liked = true;
        $newLike = null;

        if ($existingLike) {
            // Si l'utilisateur clique sur le même type de réaction, on supprime sa réaction
            if ($existingLike->getReactionType() === $reactionType) {
                $entityManager->remove($existingLike);
                $clickedSameReaction = true;
                $liked = false;
                $this->addFlash('success', 'Vous avez retiré votre réaction');
            } else {
                // Si l'utilisateur clique sur un type de réaction différent, on met à jour sa réaction
                $existingLike->setReactionType($reactionType);
                $newLike = $existingLike;
                $this->addFlash('success', 'Votre réaction a été modifiée');
            }
        } else {
            // Sinon, on ajoute une nouvelle réaction
            $like = new PostLike();
            $like->setUser($user);
            $like->setPost($post);
            $like->setReactionType($reactionType);
            $entityManager->persist($like);
            $newLike = $like;
            $this->addFlash('success', 'Vous avez ajouté une réaction');
        }

        // Mettre à jour les compteurs de likes et de réactions
        if (!$clickedSameReaction) {
            $post->updateLikesCounter();
            $post->updateReactionCounts();
        }

        $entityManager->flush();

        // Envoyer une notification à l'auteur du post
        if ($newLike !== null) {
            $notificationService->notifyPostLike($newLike);
        }

        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'liked' => $liked,
                'likesCount' => $post->getLikesCount()
            ]);
        }

        // Rediriger vers la page d'origine
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_home');
    }

    #[Route('/post/debug', name: 'app_post_debug', methods: ['GET'])]
    public function debug(PostRepository $postRepository): Response
    {
        // Cette route est désactivée en production
        if ($this->getParameter('kernel.environment') === 'prod') {
            throw $this->createNotFoundException('Cette page n\'est disponible qu\'en environnement de développement');
        }

        $posts = $postRepository->findAllOrderedByDate();

        // Retourner directement les données au format JSON pour le débogage
        $postsData = [];
        foreach ($posts as $post) {
            $postsData[] = [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'author' => $post->getAuthor()->getFullName(),
                'likesCount' => $post->getLikesCount(),
                'commentsCount' => $post->getCommentsCount(),
                'sharesCount' => $post->getSharesCount()
            ];
        }

        return $this->json([
            'posts' => $postsData,
            'count' => count($postsData),
            'timestamp' => new \DateTime()
        ]);
    }

    #[Route('/post/{id}/share', name: 'app_post_share', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function share(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
        // Création automatique du partage
        $share = new PostShare();
        $share->setUser($this->getUser());
        $share->setPost($post);

        $entityManager->persist($share);
        $post->updateSharesCounter(); // Mettre à jour le compteur
        $entityManager->flush();

        // Envoyer une notification
        $notificationService->notifyPostShare($share);

        // Rediriger vers la page d'accueil pour voir le partage dans le fil d'actualité
        return $this->redirectToRoute('app_home');
    }

    #[Route('/post/comment/{id}/reply', name: 'app_post_comment_reply', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function replyToComment(
        PostComment $parentComment,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
        $comment = new PostComment();
        $comment->setAuthor($this->getUser());
        $comment->setPost($parentComment->getPost());
        $comment->setParent($parentComment);

        $form = $this->createForm(PostCommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            // Envoyer une notification
            $notificationService->notifyPostComment($comment);

            $this->addFlash('success', 'Votre réponse a été ajoutée avec succès !');
            return $this->redirectToRoute('app_post_show', ['id' => $parentComment->getPost()->getId()]);
        }

        return $this->render('post/reply.html.twig', [
            'parentComment' => $parentComment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/hashtag/{name}', name: 'app_hashtag_show')]
    public function showHashtag(
        string $name,
        HashtagRepository $hashtagRepository,
        PostRepository $postRepository
    ): Response {
        // Trouver le hashtag
        $hashtag = $hashtagRepository->findOneBy(['name' => ltrim($name, '#')]);

        if (!$hashtag) {
            throw $this->createNotFoundException('Ce hashtag n\'existe pas');
        }

        // Récupérer les posts avec ce hashtag
        $posts = $postRepository->findByHashtag($hashtag);

        // Récupérer les hashtags tendance
        $trendingHashtags = $hashtagRepository->findTrending(5);

        return $this->render('post/hashtag.html.twig', [
            'hashtag' => $hashtag,
            'posts' => $posts,
            'trendingHashtags' => $trendingHashtags
        ]);
    }

    #[Route('/hashtags/trending', name: 'app_hashtags_trending')]
    public function trendingHashtags(HashtagRepository $hashtagRepository): Response
    {
        $hashtags = $hashtagRepository->findTrending(20);

        return $this->render('post/trending_hashtags.html.twig', [
            'hashtags' => $hashtags
        ]);
    }

    #[Route('/post/{id}/comments', name: 'app_post_comments', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getComments(
        Post $post,
        PostCommentRepository $commentRepository,
        Request $request
    ): Response {
        // Récupérer les commentaires
        $comments = $commentRepository->findBy(
            ['post' => $post, 'parent' => null],
            ['createdAt' => 'DESC']
        );

        // Si ce n'est pas une requête AJAX, rediriger vers la page du post
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $formattedComments = [];
        foreach ($comments as $comment) {
            $formattedComments[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'authorId' => $comment->getAuthor()->getId(),
                'authorName' => $comment->getAuthor()->getFullName(),
                'authorProfilePicture' => $comment->getAuthor()->getProfilePicture(),
                'createdAt' => $comment->getCreatedAt()->format('d/m/Y à H:i'),
                'repliesCount' => count($comment->getReplies())
            ];
        }

        return $this->json([
            'success' => true,
            'comments' => $formattedComments
        ]);
    }

    #[Route('/post/{id}/comment/add', name: 'app_post_comment_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addComment(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
        // Si ce n'est pas une requête AJAX, rediriger vers la page du post
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;

        if (!$content) {
            return $this->json([
                'success' => false,
                'error' => 'Le contenu ne peut pas être vide'
            ]);
        }

        $comment = new PostComment();
        $comment->setContent($content);
        $comment->setAuthor($this->getUser());
        $comment->setPost($post);

        $entityManager->persist($comment);
        $post->updateCommentsCounter();
        $entityManager->flush();

        // Envoyer une notification
        $notificationService->notifyPostComment($comment);

        return $this->json([
            'success' => true,
            'commentId' => $comment->getId(),
            'authorName' => $comment->getAuthor()->getFullName(),
            'authorPicture' => $comment->getAuthor()->getProfilePicture(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('d/m/Y à H:i')
        ]);
    }

    #[Route('/post/quick-create', name: 'app_post_quick_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function quickCreate(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        HashtagRepository $hashtagRepository,
        UserRepository $userRepository,
        NotificationService $notificationService,
        ContentProcessorService $contentProcessor,
        FileUploader $fileUploader
    ): Response {
        try {
            // Journaliser les informations de la requête
            $this->logger->info('Tentative de création de post rapide', [
                'request_content' => $request->request->all(),
                'has_files' => $request->files->count() > 0
            ]);

            // Vérifier que la requête contient des données
            if ($request->request->count() === 0 && $request->files->count() === 0) {
                throw new \Exception('La requête ne contient aucune donnée');
            }

            // Vérifier que le contenu n'est pas vide
            $content = $request->request->get('content');
            if (!$content || trim($content) === '') {
                throw new \Exception('Le contenu du post ne peut pas être vide');
            }

            $post = new Post();
            $post->setAuthor($this->getUser());

            // Récupérer le titre s'il existe, ou définir une chaîne vide
            $title = $request->request->get('title');
            $post->setTitle($title ? trim($title) : '');

            $post->setContent(trim($content));

            // Gérer l'image si présente
            $imageFile = $request->files->get('imageFile');
            if ($imageFile) {
                $this->logger->info('Image détectée', [
                    'filename' => $imageFile->getClientOriginalName(),
                    'size' => $imageFile->getSize(),
                    'mime_type' => $imageFile->getMimeType()
                ]);

                try {
                    $newFilename = $fileUploader->upload($imageFile, 'posts_directory');
                    $post->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de l\'upload de l\'image', [
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }

            // IMPORTANT: Persister et flusher avant de traiter le contenu
            // pour s'assurer que l'ID est généré
            try {
                $entityManager->persist($post);
                $entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la persistance en base de données', [
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Erreur lors de la persistance en base de données: ' . $e->getMessage());
            }

            // Traitement des hashtags et des mentions APRÈS avoir enregistré le post
            try {
                $contentProcessor->processPostContent($post);
                // Deuxième flush pour enregistrer les hashtags et mentions
                $entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors du traitement du contenu', [
                    'error' => $e->getMessage(),
                    'post_id' => $post->getId()
                ]);
                // Ne pas lancer d'exception ici, le post a déjà été créé
                // On peut continuer avec l'ID existant
            }

            // Récupérer l'ID après le flush
            $postId = $post->getId();
            if (!$postId || !is_numeric($postId) || intval($postId) <= 0) {
                $this->logger->error('ID du post invalide', [
                    'post_id' => $postId
                ]);
                throw new \Exception('L\'ID du post n\'a pas été généré correctement');
            }

            // Générer l'URL avec l'ID vérifié
            $postUrl = $this->generateUrl('app_post_show', ['id' => $postId]);

            // Générer l'URL de la page d'accueil pour y retourner
            $homeUrl = $this->generateUrl('app_home');

            $this->logger->info('Post créé avec succès', [
                'post_id' => $postId,
                'post_url' => $postUrl,    // URL du post pour référence
                'redirect_url' => $homeUrl
            ]);

            // Retourner une réponse JSON avec des informations détaillées
            return $this->json([
                'success' => true,
                'postId' => $postId,
                'postUrl' => $postUrl,    // URL du post pour référence
                'redirect' => $homeUrl,   // Rediriger vers la page d'accueil
                'debug_info' => [
                    'post_id_type' => gettype($postId),
                    'post_id_value' => $postId,
                    'route_name' => 'app_home'
                ]
            ]);
        } catch (\Exception $e) {
            // Logger l'erreur côté serveur pour une analyse plus approfondie
            $this->logger->error('Erreur de création de post: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_content' => $request->request->all(),
                'has_files' => $request->files->count() > 0,
                'request_content_type' => $request->headers->get('Content-Type')
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la création du post: ' . $e->getMessage(),
                'debug_info' => [
                    'request_parameters' => $request->request->count(),
                    'request_files' => $request->files->count(),
                    'content_type' => $request->headers->get('Content-Type')
                ]
            ], 400);
        }
    }
}
