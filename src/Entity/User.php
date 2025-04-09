<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Traits\UserProfileTrait;
use App\Entity\Traits\UserSocialTrait;
use App\Entity\Traits\UserDocumentsTrait;
use App\Entity\Traits\UserProfessionalTrait;
use DateTimeImmutable;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cette adresse email')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'user' => User::class,
    'recruiter' => 'App\Entity\Recruiter',
    'applicant' => 'App\Entity\Applicant'
])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use UserProfileTrait;
    use UserSocialTrait;
    use UserDocumentsTrait;
    use UserProfessionalTrait;

    public const ROLE_POSTULANT = 'ROLE_POSTULANT';
    public const ROLE_RECRUTEUR = 'ROLE_RECRUTEUR';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $identifier = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    protected ?string $email = null;

    #[ORM\Column]
    protected array $roles = [];

    #[ORM\Column]
    protected ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $stripeCustomerId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: JobOffer::class, orphanRemoval: true)]
    protected Collection $jobOffers;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: JobApplication::class, orphanRemoval: true)]
    protected Collection $applications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, orphanRemoval: true)]
    protected Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Favorite::class, orphanRemoval: true)]
    protected Collection $favorites;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: RecruiterSubscription::class, orphanRemoval: true)]
    protected Collection $subscriptions;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: Interview::class)]
    protected Collection $recruiterInterviews;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: Interview::class)]
    protected Collection $applicantInterviews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Education::class, orphanRemoval: true)]
    protected Collection $educationCollection;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WorkExperience::class, orphanRemoval: true)]
    protected Collection $workExperiences;

    #[ORM\Column(type: 'boolean')]
    protected bool $isVerified = false;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->createdAt = new DateTimeImmutable();
        $this->jobOffers = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->recruiterInterviews = new ArrayCollection();
        $this->applicantInterviews = new ArrayCollection();
        $this->educationCollection = new ArrayCollection();
        $this->workExperiences = new ArrayCollection();
        $this->skills = [];
        $this->documents = [];
        $this->isVerified = false;
        $this->initializeSocialCollections();
        $this->initializeProfessionalCollections();
    }

    public function getId(): ?int
    {
        return $this->identifier;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Intentionally left empty
    }

    public function isPostulant(): bool
    {
        return in_array(self::ROLE_POSTULANT, $this->roles);
    }

    public function isApplicant(): bool
    {
        return $this->isPostulant();
    }

    public function isRecruiter(): bool
    {
        return in_array(self::ROLE_RECRUTEUR, $this->roles);
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    // Méthodes pour les collections restantes
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function getRecruiterInterviews(): Collection
    {
        return $this->recruiterInterviews;
    }

    public function getApplicantInterviews(): Collection
    {
        return $this->applicantInterviews;
    }

    public function getEducationCollection(): Collection
    {
        return $this->educationCollection;
    }

    public function getWorkExperiences(): Collection
    {
        return $this->workExperiences;
    }
}
