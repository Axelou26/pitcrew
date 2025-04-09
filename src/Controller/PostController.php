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

class PostController extends AbstractController
{
    public function __construct(
        private PostService $postService,
        private PostInteractionService $interactionService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/posts', name: 'app_post_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_dashboard_posts');
    }

    #[Route('/post/new', name: 'app_post_new', methods: ['GET', 'POST'])]
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

    #[Route('/post/{id}', name: 'app_post_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post
        ]);
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
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

    #[Route('/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('POST_DELETE', 'post')]
    public function delete(Request $request, Post $post): Response
    {
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $this->postService->deletePost($post);
            $this->addFlash('success', 'Le post a été supprimé avec succès !');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/post/{id}/like', name: 'app_post_like')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function like(Post $post): JsonResponse
    {
        $isLiked = $this->interactionService->toggleLike($post, $this->getUser());

        return $this->json([
            'success' => true,
            'isLiked' => $isLiked,
            'likesCount' => $post->getLikes()->count()
        ]);
    }

    #[Route('/post/{id}/share', name: 'app_post_share')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function share(Post $post, Request $request): JsonResponse
    {
        $comment = $request->request->get('comment');
        $share = $this->interactionService->sharePost($post, $this->getUser(), $comment);

        return $this->json([
            'success' => true,
            'sharesCount' => $post->getShares()->count()
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
}
