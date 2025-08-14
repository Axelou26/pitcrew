<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\PostBasicTrait;
use App\Entity\Trait\PostCommentsTrait;
use App\Entity\Trait\PostCountersTrait;
use App\Entity\Trait\PostReactionsTrait;
use App\Entity\Trait\PostTaggingTrait;
use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
#[ORM\HasLifecycleCallbacks]
class Post
{
    use PostBasicTrait;
    use PostCommentsTrait;
    use PostCountersTrait;
    use PostReactionsTrait;
    use PostTaggingTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\OneToMany(targetEntity: PostLike::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $likes;

    #[ORM\OneToMany(targetEntity: PostComment::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $comments;

    #[ORM\ManyToMany(targetEntity: Hashtag::class, inversedBy: 'posts', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'post_hashtags')]
    private Collection $hashtags;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'reposts')]
    #[ORM\JoinColumn(name: 'original_post_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Post $originalPost = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'originalPost')]
    private Collection $reposts;

    #[ORM\Column(options: ['default' => 0])]
    private int $likesCounter = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $imageUrls = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $sharesCounter = 0;

    public function __construct()
    {
        $this->createdAt       = new DateTimeImmutable();
        $this->likes           = new ArrayCollection();
        $this->comments        = new ArrayCollection();
        $this->hashtags        = new ArrayCollection();
        $this->mentions        = [];
        $this->likesCounter    = 0;
        $this->commentsCounter = 0;
        $this->reposts         = new ArrayCollection();
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

    public function setTitle(?string $title): self
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

    public function getCreatedAt(): ?\DateTimeImmutable
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
            if ($like->getPost() === $this) {
                $like->setPost(null);
            }
            $this->likesCounter = max(0, $this->likesCounter - 1);
        }

        return $this;
    }

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
    public function getHashtags(): ?Collection
    {
        return $this->hashtags;
    }

    /**
     * @param null|Collection<int, Hashtag> $hashtags
     */
    public function setHashtags(?Collection $hashtags): self
    {
        $this->hashtags = $hashtags;

        return $this;
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
     * @return array<int, string>
     */
    public function getMentions(): array
    {
        return $this->mentions ?? [];
    }

    /**
     * @param array<int, string> $mentions
     */
    public function setMentions(array $mentions): self
    {
        $this->mentions = array_values(
            array_filter(
                $mentions,
                fn ($mention) => \is_string($mention) && $mention !== ''
            )
        );

        return $this;
    }

    /**
     * Ajoute une mention d'utilisateur.
     */
    public function addMention(User $user): static
    {
        $userId = $user->getId();
        if (!\in_array($userId, $this->mentions, true)) {
            $this->mentions[] = $userId;
        }

        return $this;
    }

    /**
     * Récupère le like d'un utilisateur pour ce post.
     */
    public function getUserReaction(User $user): ?PostLike
    {
        foreach ($this->likes as $like) {
            if ($like->getUser() === $user) {
                return $like;
            }
        }

        return null;
    }

    public function getOriginalPost(): ?self
    {
        return $this->originalPost;
    }

    public function setOriginalPost(?self $originalPost): static
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

    public function addRepost(self $repost): static
    {
        if (!$this->reposts->contains($repost)) {
            $this->reposts->add($repost);
            $repost->setOriginalPost($this);
        }

        return $this;
    }

    public function removeRepost(self $repost): static
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

    public function hasHashtag(string $hashtag): bool
    {
        $hashtags = $this->getHashtags();
        if ($hashtags === null) {
            return false;
        }

        foreach ($hashtags as $postHashtag) {
            if ($postHashtag->getName() === $hashtag) {
                return true;
            }
        }

        return false;
    }

    public function getShares(): Collection
    {
        return $this->reposts;
    }

    public function setSharesCounter(int $sharesCounter): static
    {
        $this->sharesCounter = $sharesCounter;

        return $this;
    }

    public function setImageUrls(array $imageUrls): static
    {
        $this->imageUrls = $imageUrls;

        return $this;
    }
}
