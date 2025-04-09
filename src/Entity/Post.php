<?php

namespace App\Entity;

use App\Repository\PostRepository;
use App\Entity\Trait\PostReactionsTrait;
use App\Entity\Trait\PostCommentsTrait;
use App\Entity\Trait\PostSharesTrait;
use App\Entity\Trait\PostTaggingTrait;
use App\Entity\Trait\PostMentionsTrait;
use App\Entity\Trait\PostReactionCountsTrait;
use App\Entity\Trait\PostCountersTrait;
use App\Entity\Trait\PostBasicTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
#[ORM\HasLifecycleCallbacks]
class Post
{
    use PostReactionsTrait;
    use PostCommentsTrait;
    use PostSharesTrait;
    use PostTaggingTrait;
    use PostMentionsTrait;
    use PostReactionCountsTrait;
    use PostCountersTrait;
    use PostBasicTrait;

    /**
     * @SuppressWarnings("PHPMD.ShortVariable")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PostLike::class, orphanRemoval: true)]
    private Collection $likes;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PostComment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PostShare::class, orphanRemoval: true)]
    private Collection $shares;

    #[ORM\ManyToMany(targetEntity: Hashtag::class, inversedBy: 'posts')]
    private Collection $hashtags;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->likes = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->shares = new ArrayCollection();
        $this->hashtags = new ArrayCollection();
        $this->likesCounter = 0;
        $this->commentsCounter = 0;
        $this->sharesCounter = 0;
        $this->mentions = [];
        $this->reactionCounts = $this->initializeReactionCounts();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return Collection<int, PostLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(PostLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setPost($this);
            $this->likesCounter++;
        }
        return $this;
    }

    public function removeLike(PostLike $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getPost() === $this) {
                $like->setPost(null);
            }
            $this->likesCounter = max(0, $this->likesCounter - 1);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getLikesCount(): int
    {
        return $this->likesCounter;
    }

    public function updateLikesCounter(): void
    {
        $this->likesCounter = $this->likes->count();
    }

    public function isLikedByUser(User $user): bool
    {
        foreach ($this->likes as $like) {
            if ($like->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection<int, PostComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(PostComment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
            $this->commentsCounter++;
        }
        return $this;
    }

    public function removeComment(PostComment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
            $this->commentsCounter = max(0, $this->commentsCounter - 1);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getCommentsCount(): int
    {
        return $this->commentsCounter;
    }

    public function updateCommentsCounter(): void
    {
        $this->commentsCounter = $this->comments->count();
    }

    /**
     * @return Collection<int, PostShare>
     */
    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function addShare(PostShare $share): static
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setPost($this);
            $this->sharesCounter++;
        }
        return $this;
    }

    public function removeShare(PostShare $share): static
    {
        if ($this->shares->removeElement($share)) {
            // set the owning side to null (unless already changed)
            if ($share->getPost() === $this) {
                $share->setPost(null);
            }
            $this->sharesCounter = max(0, $this->sharesCounter - 1);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getSharesCount(): int
    {
        return $this->sharesCounter;
    }

    public function updateSharesCounter(): void
    {
        $this->sharesCounter = $this->shares->count();
    }

    /**
     * Met à jour tous les compteurs en fonction du contenu des collections
     */
    public function updateAllCounters(): void
    {
        $this->updateLikesCounter();
        $this->updateCommentsCounter();
        $this->updateSharesCounter();
    }

    public function getLikesCounter(): int
    {
        return $this->likesCounter;
    }

    public function setLikesCounter(int $likesCounter): static
    {
        $this->likesCounter = $likesCounter;
        return $this;
    }

    public function getCommentsCounter(): int
    {
        return $this->commentsCounter;
    }

    public function setCommentsCounter(int $commentsCounter): static
    {
        $this->commentsCounter = $commentsCounter;
        return $this;
    }

    public function getSharesCounter(): int
    {
        return $this->sharesCounter;
    }

    public function setSharesCounter(int $sharesCounter): static
    {
        $this->sharesCounter = $sharesCounter;
        return $this;
    }

    /**
     * @return Collection<int, Hashtag>
     */
    public function getHashtags(): Collection
    {
        return $this->hashtags;
    }

    public function addHashtag(Hashtag $hashtag): self
    {
        if (!$this->hashtags->contains($hashtag)) {
            $this->hashtags->add($hashtag);
            $hashtag->getPosts()->add($this);
        }

        return $this;
    }

    public function removeHashtag(Hashtag $hashtag): self
    {
        if ($this->hashtags->removeElement($hashtag)) {
            $hashtag->getPosts()->removeElement($this);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getMentions(): ?array
    {
        return $this->mentions;
    }

    /**
     * @param array|null $mentions
     */
    public function setMentions(?array $mentions): static
    {
        $this->mentions = $mentions ?? [];
        return $this;
    }

    /**
     * Ajoute une mention d'utilisateur
     */
    public function addMention(User $user): static
    {
        $userId = $user->getId();
        if (!in_array($userId, $this->mentions)) {
            $this->mentions[] = $userId;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getReactionCounts(): ?array
    {
        return $this->reactionCounts;
    }

    /**
     * @param array|null $reactionCounts
     */
    public function setReactionCounts(?array $reactionCounts): static
    {
        $this->reactionCounts = $reactionCounts;
        return $this;
    }

    /**
     * Retourne le nombre de réactions d'un type spécifique
     */
    public function getReactionCount(string $type): int
    {
        if ($this->reactionCounts === null) {
            return 0;
        }

        // Vérifier d'abord la clé exacte
        if (isset($this->reactionCounts[$type])) {
            return $this->reactionCounts[$type];
        }

        // Vérifier si c'est 'like' mais stocké comme 'likes'
        if ($type === 'like' && isset($this->reactionCounts['likes'])) {
            return $this->reactionCounts['likes'];
        }

        // Vérifier si c'est 'likes' mais stocké comme 'like'
        if ($type === 'likes' && isset($this->reactionCounts['like'])) {
            return $this->reactionCounts['like'];
        }

        return 0;
    }

    /**
     * Récupère le type de réaction d'un utilisateur pour ce post
     */
    public function getUserReaction(User $user): ?string
    {
        foreach ($this->likes as $like) {
            if ($like->getUser() === $user) {
                return $like->getReactionType();
            }
        }
        return null;
    }

    /**
     * Alias pour getUserReaction()
     * Cette méthode est utilisée dans les templates
     */
    public function getUserReactionType(User $user): ?string
    {
        return $this->getUserReaction($user);
    }
}
