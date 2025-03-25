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

class PostController extends AbstractController
{
    #[Route('/posts', name: 'app_post_index', methods: ['GET'])]
    public function index(
        PostRepository $postRepository, 
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();
        
        // Récupérer les posts avec pagination
        $posts = $postRepository->findAllOrderedByDate();
        
        // Si l'utilisateur est connecté, récupérer des utilisateurs suggérés
        $suggestedUsers = [];
        if ($user) {
            // Récupérer les utilisateurs suggérés (limités à 5)
            $userRepository = $entityManager->getRepository(\App\Entity\User::class);
            $suggestedUsers = $userRepository->findSuggestedUsers($user, 5);
        }
        
        // Récupérer les tendances (tags populaires)
        $trends = [
            [
                'tag' => 'F1',
                'title' => 'Grand Prix de Monaco 2025',
                'count' => '1.5K'
            ],
            [
                'tag' => 'Carrière',
                'title' => 'Les métiers d\'avenir en F1',
                'count' => '856'
            ],
            [
                'tag' => 'Technologie',
                'title' => 'Innovations en aérodynamique',
                'count' => '543'
            ]
        ];
        
        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'suggestedUsers' => $suggestedUsers,
            'trends' => $trends
        ]);
    }

    #[Route('/post/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $slugger,
        UserRepository $userRepository,
        HashtagRepository $hashtagRepository
    ): Response {
        $post = new Post();
        $post->setAuthor($this->getUser());
        
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('posts_directory'),
                        $newFilename
                    );
                    $post->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                }
            }
            
            // Traitement des hashtags
            $hashtags = $post->extractHashtags();
            foreach ($hashtags as $tagName) {
                $hashtag = $hashtagRepository->findOrCreate($tagName);
                $post->addHashtag($hashtag);
            }
            
            // Traitement des mentions
            $mentions = $post->extractMentions();
            foreach ($mentions as $username) {
                $user = $userRepository->findOneBy(['username' => $username]);
                if ($user) {
                    $post->addMention($user);
                    
                    // Ici on pourrait créer une notification pour l'utilisateur mentionné
                }
            }

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Votre post a été publié avec succès !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('post/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/post/{id}', name: 'app_post_show', methods: ['GET', 'POST'])]
    public function show(
        Post $post, 
        Request $request, 
        EntityManagerInterface $entityManager, 
        PostCommentRepository $commentRepository,
        NotificationService $notificationService
    ): Response {
        // Formulaire pour ajouter un commentaire
        $comment = new PostComment();
        $comment->setAuthor($this->getUser());
        $comment->setPost($post);
        
        $commentForm = $this->createForm(PostCommentType::class, $comment);
        $commentForm->handleRequest($request);
        
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $entityManager->persist($comment);
            $post->updateCommentsCounter(); // Mettre à jour le compteur
            $entityManager->flush();
            
            // Envoyer une notification
            $notificationService->notifyPostComment($comment);
            
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès !');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }
        
        // Récupérer les commentaires (triés par date décroissante)
        $comments = $commentRepository->findBy(
            ['post' => $post, 'parent' => null],
            ['createdAt' => 'DESC']
        );
        
        return $this->render('post/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('POST_EDIT', 'post')]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('posts_directory'),
                        $newFilename
                    );
                    
                    // Supprimer l'ancienne image si elle existe
                    if ($post->getImage()) {
                        $oldImagePath = $this->getParameter('posts_directory').'/'.$post->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $post->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre post a été modifié avec succès !');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted('POST_DELETE', 'post')]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Le post a été supprimé avec succès !');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/post/create', name: 'app_post_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $content = $request->request->get('content');
        if (!$content) {
            $this->addFlash('error', 'Le contenu ne peut pas être vide');
            return $this->redirectToRoute('app_home');
        }

        $post = new Post();
        $post->setContent($content);
        $post->setAuthor($this->getUser());

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('posts_directory'),
                    $newFilename
                );
                $post->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
            }
        }

        $entityManager->persist($post);
        $entityManager->flush();

        $this->addFlash('success', 'Votre publication a été créée');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/post/{id}/like', name: 'app_post_like')]
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
        $posts = $postRepository->findAllOrderedByDate();
        
        return $this->render('debug_post.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/post/{id}/share', name: 'app_post_share', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function share(
        Post $post, 
        Request $request, 
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
        $share = new PostShare();
        $share->setUser($this->getUser());
        $share->setPost($post);
        
        $form = $this->createForm(PostShareType::class, $share);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($share);
            $post->updateSharesCounter(); // Mettre à jour le compteur
            $entityManager->flush();
            
            // Envoyer une notification
            $notificationService->notifyPostShare($share);
            
            $this->addFlash('success', 'Publication partagée avec succès !');
            return $this->redirectToRoute('app_home');
        }
        
        return $this->render('post/share.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/post/comment/{id}/reply', name: 'app_post_comment_reply', methods: ['GET', 'POST'])]
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

    #[Route('/post/{id}/comments', name: 'app_post_comments', methods: ['GET'])]
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
    
    #[Route('/post/{id}/comment/add', name: 'app_post_comment_add', methods: ['POST'])]
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
} 