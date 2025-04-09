<?php

namespace App\Entity;

use App\Repository\HashtagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: HashtagRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce hashtag existe déjà')]
class Hashtag
{
    /**
     * @SuppressWarnings("PHPMD.ShortVariable")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $name = null;

    #[ORM\Column]
    private int $usageCount = 0;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'hashtags')]
    private Collection $posts;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->lastUsedAt = new DateTimeImmutable();
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        // Enlever le # si présent et convertir en minuscules
        $name = ltrim($name, '#');
        $name = strtolower($name);

        $this->name = $name;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function incrementUsageCount(): static
    {
        $this->usageCount++;
        $this->lastUsedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?DateTimeImmutable $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->getHashtags()->add($this);
            $this->incrementUsageCount();
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->removeElement($post)) {
            $post->getHashtags()->removeElement($this);
        }

        return $this;
    }

    public function getFormattedName(): string
    {
        return '#' . $this->name;
    }

    public function __toString(): string
    {
        return $this->getFormattedName();
    }
}
