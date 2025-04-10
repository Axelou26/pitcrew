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
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: JobApplication::class, orphanRemoval: true)]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: RecruiterSubscription::class, orphanRemoval: true)]
    private Collection $subscriptions;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: Interview::class)]
    private Collection $recruiterInterviews;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: Interview::class)]
    private Collection $applicantInterviews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Education::class, orphanRemoval: true)]
    private Collection $education;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WorkExperience::class, orphanRemoval: true)]
    private Collection $workExperiences;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SupportTicket::class, orphanRemoval: true)]
    private Collection $supportTickets;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->createdAt = new DateTimeImmutable();
        $this->applications = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->recruiterInterviews = new ArrayCollection();
        $this->applicantInterviews = new ArrayCollection();
        $this->education = new ArrayCollection();
        $this->workExperiences = new ArrayCollection();
        $this->supportTickets = new ArrayCollection();
        $this->skills = [];
        $this->documents = [];
        $this->isVerified = false;
        $this->initializeSocialCollections();
        $this->initializeProfessionalCollections();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(JobApplication $application): static
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setApplicant($this);
        }
        return $this;
    }

    public function removeApplication(JobApplication $application): static
    {
        if ($this->applications->removeElement($application)) {
            if ($application->getApplicant() === $this) {
                $application->setApplicant(null);
            }
        }
        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }
        return $this;
    }

    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setUser($this);
        }
        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }
        return $this;
    }

    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(RecruiterSubscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setRecruiter($this);
        }
        return $this;
    }

    public function removeSubscription(RecruiterSubscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getRecruiter() === $this) {
                $subscription->setRecruiter(null);
            }
        }
        return $this;
    }

    public function getInterviewsAsRecruiter(): Collection
    {
        return $this->recruiterInterviews;
    }

    public function addInterviewAsRecruiter(Interview $interview): static
    {
        if (!$this->recruiterInterviews->contains($interview)) {
            $this->recruiterInterviews->add($interview);
            $interview->setRecruiter($this);
        }
        return $this;
    }

    public function removeInterviewAsRecruiter(Interview $interview): static
    {
        if ($this->recruiterInterviews->removeElement($interview)) {
            if ($interview->getRecruiter() === $this) {
                $interview->setRecruiter(null);
            }
        }
        return $this;
    }

    public function getInterviewsAsApplicant(): Collection
    {
        return $this->applicantInterviews;
    }

    public function addInterviewAsApplicant(Interview $interview): static
    {
        if (!$this->applicantInterviews->contains($interview)) {
            $this->applicantInterviews->add($interview);
            $interview->setApplicant($this);
        }
        return $this;
    }

    public function removeInterviewAsApplicant(Interview $interview): static
    {
        if ($this->applicantInterviews->removeElement($interview)) {
            if ($interview->getApplicant() === $this) {
                $interview->setApplicant(null);
            }
        }
        return $this;
    }

    public function getEducation(): Collection
    {
        return $this->education;
    }

    public function addEducation(Education $education): static
    {
        if (!$this->education->contains($education)) {
            $this->education->add($education);
            $education->setUser($this);
        }
        return $this;
    }

    public function removeEducation(Education $education): static
    {
        if ($this->education->removeElement($education)) {
            if ($education->getUser() === $this) {
                $education->setUser(null);
            }
        }
        return $this;
    }

    public function getWorkExperiences(): Collection
    {
        return $this->workExperiences;
    }

    public function addWorkExperience(WorkExperience $workExperience): static
    {
        if (!$this->workExperiences->contains($workExperience)) {
            $this->workExperiences->add($workExperience);
            $workExperience->setUser($this);
        }
        return $this;
    }

    public function removeWorkExperience(WorkExperience $workExperience): static
    {
        if ($this->workExperiences->removeElement($workExperience)) {
            if ($workExperience->getUser() === $this) {
                $workExperience->setUser(null);
            }
        }
        return $this;
    }

    public function getSupportTickets(): Collection
    {
        return $this->supportTickets;
    }

    public function addSupportTicket(SupportTicket $supportTicket): static
    {
        if (!$this->supportTickets->contains($supportTicket)) {
            $this->supportTickets->add($supportTicket);
            $supportTicket->setUser($this);
        }
        return $this;
    }

    public function removeSupportTicket(SupportTicket $supportTicket): static
    {
        if ($this->supportTickets->removeElement($supportTicket)) {
            if ($supportTicket->getUser() === $this) {
                $supportTicket->setUser(null);
            }
        }
        return $this;
    }
}
