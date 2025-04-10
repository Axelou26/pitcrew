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

#[Route('/post')]
class PostController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        private PostService $postService,
        private PostInteractionService $interactionService,
        private LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
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
                return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
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
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('POST_EDIT', 'post')]
    public function edit(Request $request, Post $post): Response
    {
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

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('POST_DELETE', 'post')]
    public function delete(Request $request, Post $post): Response
    {
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $this->postService->deletePost($post);
            $this->addFlash('success', 'Le post a été supprimé avec succès !');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/{id}/like', name: 'app_post_like', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function like(Post $post, Request $request): JsonResponse
    {
        $reactionType = $request->request->get('reactionType');
        if (empty($reactionType) || !array_key_exists($reactionType, PostLike::REACTIONS)) {
            return $this->json([
                'success' => false,
                'message' => 'Type de réaction invalide ou manquant.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $activeReactionType = $this->interactionService->toggleLike($post, $this->getUser(), $reactionType);
            $this->entityManager->refresh($post);

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
            // Récupérer le commentaire soit du JSON soit des paramètres de formulaire
            $comment = '';
            if ($request->getContentTypeFormat() === 'json') {
                $data = json_decode($request->getContent(), true);
                $comment = $data['comment'] ?? '';
            } else {
                $comment = $request->request->get('share-comment', '');
            }

            // Créer le nouveau post
            $sharedPost = new Post(); // Le constructeur initialise createdAt
            $sharedPost->setContent($comment ?: 'A partagé un post');
            $sharedPost->setAuthor($this->getUser());
            $sharedPost->setOriginalPost($originalPost);
            
            // Définir un titre basé sur le post original
            $originalTitle = $originalPost->getTitle();
            $sharedPost->setTitle($originalTitle ? "Repost: {$originalTitle}" : "Post partagé");

            // Si le post original a une image, la copier
            if ($originalPost->getImage()) {
                $sharedPost->setImage($originalPost->getImage());
                $sharedPost->setImageName($originalPost->getImageName());
            }

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

        return $this->render('post/hashtag.html.twig', [
            'hashtag' => $hashtag,
            'posts' => $posts,
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
    public function quickCreate(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->getUser()) {
            return new JsonResponse([
                'error' => 'Vous devez être connecté pour publier un post',
                'redirect' => $this->generateUrl('app_login')
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $content = $request->request->get('content');
            if (empty($content)) {
                $content = $request->request->get('fullContent');
            }

            if (empty($content)) {
                return new JsonResponse(['error' => 'Le contenu est obligatoire'], Response::HTTP_BAD_REQUEST);
            }

            $title = $request->request->get('title');
            $imageFile = $request->files->get('image');

            $post = $this->postService->createPost(
                $content,
                $this->getUser(),
                $title ?: null,
                $imageFile
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Publication créée avec succès',
                'postId' => $post->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du post: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la création de la publication'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/reaction', name: 'app_post_reaction', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function addReaction(Post $post, Request $request): JsonResponse
    {
        $reactionType = $request->request->get('type');
        if (empty($reactionType)) {
            return $this->json([
                'success' => false,
                'message' => 'Le type de réaction est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $reaction = $this->interactionService->addReaction($post, $this->getUser(), $reactionType);
            
            return $this->json([
                'success' => true,
                'message' => 'Réaction ajoutée avec succès',
                'reactionId' => $reaction->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'ajout de la réaction: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'ajout de la réaction'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
