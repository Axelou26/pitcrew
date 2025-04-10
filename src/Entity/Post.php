<?php

namespace App\Entity;

use App\Repository\PostRepository;
use App\Entity\Trait\PostReactionsTrait;
use App\Entity\Trait\PostCommentsTrait;
use App\Entity\Trait\PostTaggingTrait;
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
    use PostTaggingTrait;
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

    #[ORM\ManyToMany(targetEntity: Hashtag::class, inversedBy: 'posts')]
    private Collection $hashtags;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?array $reactionCounts = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'reposts')]
    #[ORM\JoinColumn(name: "original_post_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Post $originalPost = null;

    #[ORM\OneToMany(mappedBy: 'originalPost', targetEntity: self::class)]
    private Collection $reposts;

    private function getDefaultReactionCounts(): array
    {
        return [
            'like' => 0,
            'congrats' => 0,
            'interesting' => 0,
            'support' => 0,
            'encouraging' => 0
        ];
    }

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->likes = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->hashtags = new ArrayCollection();
        $this->mentions = [];
        $this->likesCounter = 0;
        $this->commentsCounter = 0;
        $this->reactionCounts = $this->getDefaultReactionCounts();
        $this->reposts = new ArrayCollection();
        $this->updateCommentsCounter();
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

    public function getReactionCounts(): array
    {
        return $this->reactionCounts ?? $this->getDefaultReactionCounts();
    }

    public function setReactionCounts(?array $reactionCounts): static
    {
        $this->reactionCounts = $reactionCounts ?? $this->getDefaultReactionCounts();
        return $this;
    }

    public function getReactionCount(string $type): int
    {
        $counts = $this->getReactionCounts();
        
        // Vérifier d'abord la clé exacte
        if (isset($counts[$type])) {
            return $counts[$type];
        }

        // Vérifier si c'est 'like' mais stocké comme 'likes'
        if ($type === 'like' && isset($counts['likes'])) {
            return $counts['likes'];
        }

        // Vérifier si c'est 'likes' mais stocké comme 'like'
        if ($type === 'likes' && isset($counts['like'])) {
            return $counts['like'];
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

    public function getOriginalPost(): ?Post
    {
        return $this->originalPost;
    }

    public function setOriginalPost(?Post $originalPost): static
    {
        $this->originalPost = $originalPost;
        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getReposts(): Collection
    {
        return $this->reposts;
    }

    public function addRepost(Post $repost): static
    {
        if (!$this->reposts->contains($repost)) {
            $this->reposts->add($repost);
            $repost->setOriginalPost($this);
        }
        return $this;
    }

    public function removeRepost(Post $repost): static
    {
        if ($this->reposts->removeElement($repost)) {
            // set the owning side to null (unless already changed)
            if ($repost->getOriginalPost() === $this) {
                $repost->setOriginalPost(null);
            }
        }
        return $this;
    }

    public function getRepostsCount(): int
    {
        return $this->reposts->count();
    }
}
