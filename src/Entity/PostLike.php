<?php

namespace App\Entity;

use App\Repository\PostLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostLikeRepository::class)]
#[ORM\UniqueConstraint(
    name: 'unique_like',
    columns: ['user_id', 'post_id']
)]
class PostLike
{
    public const REACTION_LIKE = 'like';
    public const REACTION_CONGRATS = 'congrats';
    public const REACTION_INTERESTING = 'interesting';
    public const REACTION_SUPPORT = 'support';
    public const REACTION_ENCOURAGING = 'encouraging';

    public const REACTIONS = [
        self::REACTION_LIKE => '👍',
        self::REACTION_CONGRATS => '👏',
        self::REACTION_INTERESTING => '💡',
        self::REACTION_SUPPORT => '❤️',
        self::REACTION_ENCOURAGING => '💪',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'postLikes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20)]
    private string $reactionType = self::REACTION_LIKE;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getReactionType(): string
    {
        return $this->reactionType;
    }

    public function setReactionType(string $reactionType): static
    {
        if (!array_key_exists($reactionType, self::REACTIONS)) {
            throw new \InvalidArgumentException('Type de réaction invalide');
        }
        $this->reactionType = $reactionType;
        return $this;
    }

    public function getReactionEmoji(): string
    {
        return self::REACTIONS[$this->reactionType];
    }

    public function getReactionName(): string
    {
        $names = [
            self::REACTION_LIKE => 'J\'aime',
            self::REACTION_CONGRATS => 'Félicitations',
            self::REACTION_INTERESTING => 'Intéressant',
            self::REACTION_SUPPORT => 'Soutien',
            self::REACTION_ENCOURAGING => 'Encourageant',
        ];

        return $names[$this->reactionType];
    }
}
