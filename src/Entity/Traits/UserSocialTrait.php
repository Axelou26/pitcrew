<?php

namespace App\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Friendship;
use App\Entity\Post;
use App\Entity\PostLike;
use App\Entity\PostComment;
use App\Entity\PostShare;
use App\Entity\User;

trait UserSocialTrait
{
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Post::class, orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'requester', targetEntity: Friendship::class, orphanRemoval: true)]
    private Collection $sentFriendRequests;

    #[ORM\OneToMany(mappedBy: 'addressee', targetEntity: Friendship::class, orphanRemoval: true)]
    private Collection $receivedRequests;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PostLike::class, orphanRemoval: true)]
    private Collection $postLikes;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: PostComment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PostShare::class, orphanRemoval: true)]
    private Collection $shares;

    public bool $isFriend = false;
    public bool $pendingRequestFrom = false;
    public bool $pendingRequestTo = false;
    public ?int $pendingRequestId = null;

    public function initializeSocialCollections(): void
    {
        $this->posts = new ArrayCollection();
        $this->sentFriendRequests = new ArrayCollection();
        $this->receivedRequests = new ArrayCollection();
        $this->postLikes = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->shares = new ArrayCollection();
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }
        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }
        return $this;
    }

    public function getSentFriendRequests(): Collection
    {
        return $this->sentFriendRequests;
    }

    public function getReceivedRequests(): Collection
    {
        return $this->receivedRequests;
    }

    public function hasPendingFriendRequestWith(User $user): bool
    {
        foreach ($this->sentFriendRequests as $request) {
            if ($request->getAddressee() === $user && $request->getStatus() === 'pending') {
                return true;
            }
        }
        foreach ($this->receivedRequests as $request) {
            if ($request->getRequester() === $user && $request->getStatus() === 'pending') {
                return true;
            }
        }
        return false;
    }

    public function isFriendWith(User $user): bool
    {
        foreach ($this->sentFriendRequests as $request) {
            if ($request->getAddressee() === $user && $request->getStatus() === 'accepted') {
                return true;
            }
        }
        foreach ($this->receivedRequests as $request) {
            if ($request->getRequester() === $user && $request->getStatus() === 'accepted') {
                return true;
            }
        }
        return false;
    }

    public function getFriends(): array
    {
        $friends = [];
        foreach ($this->sentFriendRequests as $request) {
            if ($request->getStatus() === 'accepted') {
                $friends[] = $request->getAddressee();
            }
        }
        foreach ($this->receivedRequests as $request) {
            if ($request->getStatus() === 'accepted') {
                $friends[] = $request->getRequester();
            }
        }
        return $friends;
    }

    public function getPostLikes(): Collection
    {
        return $this->postLikes;
    }

    public function hasLikedPost(Post $post): bool
    {
        foreach ($this->postLikes as $like) {
            if ($like->getPost() === $post) {
                return true;
            }
        }
        return false;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function addComment(PostComment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setAuthor($this);
        }
        return $this;
    }

    public function removeComment(PostComment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }
        return $this;
    }

    public function addShare(PostShare $share): static
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setUser($this);
        }
        return $this;
    }

    public function removeShare(PostShare $share): static
    {
        if ($this->shares->removeElement($share)) {
            if ($share->getUser() === $this) {
                $share->setUser(null);
            }
        }
        return $this;
    }
}
