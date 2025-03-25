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

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cette adresse email')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['user' => User::class, 'recruiter' => 'App\Entity\Recruiter', 'applicant' => 'App\Entity\Applicant'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Constantes pour les types d'utilisateurs
    public const ROLE_POSTULANT = 'ROLE_POSTULANT';
    public const ROLE_RECRUTEUR = 'ROLE_RECRUTEUR';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $skills = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $documents = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $education = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Post::class, orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: JobOffer::class, orphanRemoval: true)]
    private Collection $jobOffers;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: JobApplication::class, orphanRemoval: true)]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'requester', targetEntity: Friendship::class, orphanRemoval: true)]
    private Collection $sentFriendRequests;

    #[ORM\OneToMany(mappedBy: 'addressee', targetEntity: Friendship::class, orphanRemoval: true)]
    private Collection $receivedFriendRequests;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PostLike::class, orphanRemoval: true)]
    private Collection $postLikes;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: PostComment::class, orphanRemoval: true)]
    private Collection $postComments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PostShare::class, orphanRemoval: true)]
    private Collection $postShares;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: RecruiterSubscription::class, orphanRemoval: true)]
    private Collection $subscriptions;

    // Propriétés dynamiques pour les relations d'amitié (non persistées)
    public $isFriend = false;
    public $hasPendingRequestFrom = false;
    public $hasPendingRequestTo = false;
    public $pendingRequestId = null;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->createdAt = new \DateTimeImmutable();
        $this->posts = new ArrayCollection();
        $this->jobOffers = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->skills = [];
        $this->documents = [];
        $this->sentFriendRequests = new ArrayCollection();
        $this->receivedFriendRequests = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->postLikes = new ArrayCollection();
        $this->postComments = new ArrayCollection();
        $this->postShares = new ArrayCollection();
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function isPostulant(): bool
    {
        return in_array('ROLE_POSTULANT', $this->roles);
    }

    /**
     * Alias pour isPostulant()
     */
    public function isApplicant(): bool
    {
        return $this->isPostulant();
    }

    public function isRecruiter(): bool
    {
        return in_array('ROLE_RECRUTEUR', $this->roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
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
            // set the owning side to null (unless already changed)
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobOffer>
     */
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    public function addJobOffer(JobOffer $jobOffer): static
    {
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->add($jobOffer);
            $jobOffer->setRecruiter($this);
        }

        return $this;
    }

    public function removeJobOffer(JobOffer $jobOffer): static
    {
        if ($this->jobOffers->removeElement($jobOffer)) {
            // set the owning side to null (unless already changed)
            if ($jobOffer->getRecruiter() === $this) {
                $jobOffer->setRecruiter(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobApplication>
     */
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
            // set the owning side to null (unless already changed)
            if ($application->getApplicant() === $this) {
                $application->setApplicant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
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
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getSkills(): ?array
    {
        return $this->skills;
    }

    public function setSkills(?array $skills): static
    {
        $this->skills = $skills;
        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;
        return $this;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): static
    {
        $this->documents = $documents;
        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): static
    {
        $this->experience = $experience;
        return $this;
    }

    public function getEducation(): ?string
    {
        return $this->education;
    }

    public function setEducation(?string $education): static
    {
        $this->education = $education;
        return $this;
    }
    
    /**
     * @return Collection<int, Friendship>
     */
    public function getSentFriendRequests(): Collection
    {
        return $this->sentFriendRequests;
    }

    public function addSentFriendRequest(Friendship $friendship): self
    {
        if (!$this->sentFriendRequests->contains($friendship)) {
            $this->sentFriendRequests->add($friendship);
            $friendship->setRequester($this);
        }

        return $this;
    }

    public function removeSentFriendRequest(Friendship $friendship): self
    {
        if ($this->sentFriendRequests->removeElement($friendship)) {
            // set the owning side to null (unless already changed)
            if ($friendship->getRequester() === $this) {
                $friendship->setRequester(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function getReceivedFriendRequests(): Collection
    {
        return $this->receivedFriendRequests;
    }

    public function addReceivedFriendRequest(Friendship $friendship): self
    {
        if (!$this->receivedFriendRequests->contains($friendship)) {
            $this->receivedFriendRequests->add($friendship);
            $friendship->setAddressee($this);
        }

        return $this;
    }

    public function removeReceivedFriendRequest(Friendship $friendship): self
    {
        if ($this->receivedFriendRequests->removeElement($friendship)) {
            // set the owning side to null (unless already changed)
            if ($friendship->getAddressee() === $this) {
                $friendship->setAddressee(null);
            }
        }

        return $this;
    }
    
    /**
     * Vérifie si l'utilisateur a une demande d'amitié en attente avec un autre utilisateur
     */
    public function hasPendingFriendRequestWith(User $user): bool
    {
        foreach ($this->sentFriendRequests as $request) {
            if ($request->getAddressee() === $user && $request->isPending()) {
                return true;
            }
        }
        
        foreach ($this->receivedFriendRequests as $request) {
            if ($request->getRequester() === $user && $request->isPending()) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifie si l'utilisateur est ami avec un autre utilisateur
     */
    public function isFriendWith(User $user): bool
    {
        foreach ($this->sentFriendRequests as $request) {
            if ($request->getAddressee() === $user && $request->isAccepted()) {
                return true;
            }
        }
        
        foreach ($this->receivedFriendRequests as $request) {
            if ($request->getRequester() === $user && $request->isAccepted()) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * @return Collection<int, Favorite>
     */
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
            // set the owning side to null (unless already changed)
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }

        return $this;
    }
    
    /**
     * Récupère les offres d'emploi en favoris
     * 
     * @return array<int, JobOffer>
     */
    public function getFavoriteJobOffers(): array
    {
        $jobOffers = [];
        
        foreach ($this->favorites as $favorite) {
            if ($favorite->isJobOfferFavorite() && $favorite->getJobOffer() !== null) {
                $jobOffers[] = $favorite->getJobOffer();
            }
        }
        
        return $jobOffers;
    }
    
    /**
     * Récupère les candidats en favoris (pour les recruteurs)
     * 
     * @return array<int, User>
     */
    public function getFavoriteCandidates(): array
    {
        $candidates = [];
        
        foreach ($this->favorites as $favorite) {
            if ($favorite->isCandidateFavorite() && $favorite->getCandidate() !== null) {
                $candidates[] = $favorite->getCandidate();
            }
        }
        
        return $candidates;
    }
    
    /**
     * Vérifie si une offre d'emploi est en favoris
     */
    public function hasJobOfferInFavorites(JobOffer $jobOffer): bool
    {
        foreach ($this->favorites as $favorite) {
            if ($favorite->isJobOfferFavorite() && $favorite->getJobOffer() === $jobOffer) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifie si un candidat est en favoris
     */
    public function hasCandidateInFavorites(User $candidate): bool
    {
        foreach ($this->favorites as $favorite) {
            if ($favorite->isCandidateFavorite() && $favorite->getCandidate() === $candidate) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * @return Collection<int, RecruiterSubscription>
     */
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
            // set the owning side to null (unless already changed)
            if ($subscription->getRecruiter() === $this) {
                $subscription->setRecruiter(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostLike>
     */
    public function getPostLikes(): Collection
    {
        return $this->postLikes;
    }

    public function addPostLike(PostLike $postLike): static
    {
        if (!$this->postLikes->contains($postLike)) {
            $this->postLikes->add($postLike);
            $postLike->setUser($this);
        }

        return $this;
    }

    public function removePostLike(PostLike $postLike): static
    {
        if ($this->postLikes->removeElement($postLike)) {
            // set the owning side to null (unless already changed)
            if ($postLike->getUser() === $this) {
                $postLike->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostComment>
     */
    public function getPostComments(): Collection
    {
        return $this->postComments;
    }

    public function addPostComment(PostComment $postComment): static
    {
        if (!$this->postComments->contains($postComment)) {
            $this->postComments->add($postComment);
            $postComment->setAuthor($this);
        }

        return $this;
    }

    public function removePostComment(PostComment $postComment): static
    {
        if ($this->postComments->removeElement($postComment)) {
            // set the owning side to null (unless already changed)
            if ($postComment->getAuthor() === $this) {
                $postComment->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostShare>
     */
    public function getPostShares(): Collection
    {
        return $this->postShares;
    }

    public function addPostShare(PostShare $postShare): static
    {
        if (!$this->postShares->contains($postShare)) {
            $this->postShares->add($postShare);
            $postShare->setUser($this);
        }

        return $this;
    }

    public function removePostShare(PostShare $postShare): static
    {
        if ($this->postShares->removeElement($postShare)) {
            // set the owning side to null (unless already changed)
            if ($postShare->getUser() === $this) {
                $postShare->setUser(null);
            }
        }

        return $this;
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
}