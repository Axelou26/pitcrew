<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['user' => 'User', 'applicant' => 'Applicant', 'recruiter' => 'Recruiter'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_POSTULANT = 'ROLE_POSTULANT';
    public const ROLE_RECRUTEUR = 'ROLE_RECRUTEUR';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'applicant', orphanRemoval: true)]
    private Collection $applications;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $favorites;

    #[ORM\OneToMany(targetEntity: RecruiterSubscription::class, mappedBy: 'recruiter', orphanRemoval: true)]
    private Collection $subscriptions;

    #[ORM\OneToMany(targetEntity: Interview::class, mappedBy: 'recruiter', orphanRemoval: true)]
    private Collection $recruiterInterviews;

    #[ORM\OneToMany(targetEntity: Interview::class, mappedBy: 'applicant', orphanRemoval: true)]
    private Collection $applicantInterviews;

    #[ORM\OneToMany(targetEntity: Education::class, mappedBy: 'applicant', orphanRemoval: true)]
    private Collection $education;

    #[ORM\OneToMany(targetEntity: WorkExperience::class, mappedBy: 'applicant', orphanRemoval: true)]
    private Collection $workExperiences;

    #[ORM\OneToMany(targetEntity: SupportTicket::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $supportTickets;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: JobOffer::class, mappedBy: 'recruiter', orphanRemoval: true)]
    private Collection $jobOffers;

    #[ORM\ManyToMany(targetEntity: JobOffer::class)]
    #[ORM\JoinTable(name: 'user_job_offer')]
    private Collection $favoriteOffers;

    #[ORM\OneToMany(targetEntity: PostComment::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(targetEntity: PostLike::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $postLikes;

    #[ORM\OneToMany(targetEntity: Friendship::class, mappedBy: 'requester', orphanRemoval: true)]
    private Collection $sentFriendships;

    #[ORM\OneToMany(targetEntity: Friendship::class, mappedBy: 'addressee', orphanRemoval: true)]
    private Collection $receivedFriendships;

    #[ORM\ManyToMany(targetEntity: Friendship::class)]
    #[ORM\JoinTable(name: 'user_friendship')]
    private Collection $friendships;

    #[ORM\ManyToMany(targetEntity: Hashtag::class)]
    #[ORM\JoinTable(name: 'user_hashtag')]
    private Collection $hashtags;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'json')]
    private array $skills = [];

    #[ORM\Column(type: 'json')]
    private array $documents = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resume = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    /**
     * Repository pour les amitiés, utilisé pour les tests.
     */
    private $friendshipRepository;

    public function __construct()
    {
        $this->applications        = new ArrayCollection();
        $this->notifications       = new ArrayCollection();
        $this->favorites           = new ArrayCollection();
        $this->subscriptions       = new ArrayCollection();
        $this->recruiterInterviews = new ArrayCollection();
        $this->applicantInterviews = new ArrayCollection();
        $this->education           = new ArrayCollection();
        $this->workExperiences     = new ArrayCollection();
        $this->supportTickets      = new ArrayCollection();
        $this->posts               = new ArrayCollection();
        $this->jobOffers           = new ArrayCollection();
        $this->favoriteOffers      = new ArrayCollection();
        $this->comments            = new ArrayCollection();
        $this->postLikes           = new ArrayCollection();
        $this->sentFriendships     = new ArrayCollection();
        $this->receivedFriendships = new ArrayCollection();
        $this->friendships         = new ArrayCollection();
        $this->hashtags            = new ArrayCollection();
        $this->createdAt           = new DateTimeImmutable();
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
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
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
        // $this->plainPassword = null;
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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): static
    {
        $this->createdAt = DateTimeImmutable::createFromInterface($createdAt);

        return $this;
    }

    /**
     * @return Collection<int, Application>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication($application): self
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setApplicant($this);
        }

        return $this;
    }

    public function removeApplication(Application $application): static
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
     * @return Collection<int, Interview>
     */
    public function getInterviewsAsRecruiter(): Collection
    {
        return $this->recruiterInterviews;
    }

    public function addRecruiterInterview(Interview $interview): static
    {
        if (!$this->recruiterInterviews->contains($interview)) {
            $this->recruiterInterviews->add($interview);
            $interview->setRecruiter($this);
        }

        return $this;
    }

    public function removeRecruiterInterview(Interview $interview): static
    {
        if ($this->recruiterInterviews->removeElement($interview)) {
            // set the owning side to null (unless already changed)
            if ($interview->getRecruiter() === $this) {
                $interview->setRecruiter(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Interview>
     */
    public function getInterviewsAsApplicant(): Collection
    {
        return $this->applicantInterviews;
    }

    public function addApplicantInterview(Interview $interview): static
    {
        if (!$this->applicantInterviews->contains($interview)) {
            $this->applicantInterviews->add($interview);
            $interview->setApplicant($this);
        }

        return $this;
    }

    public function removeApplicantInterview(Interview $interview): static
    {
        if ($this->applicantInterviews->removeElement($interview)) {
            // set the owning side to null (unless already changed)
            if ($interview->getApplicant() === $this) {
                $interview->setApplicant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Education>
     */
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
            // set the owning side to null (unless already changed)
            if ($education->getUser() === $this) {
                $education->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WorkExperience>
     */
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
            // set the owning side to null (unless already changed)
            if ($workExperience->getUser() === $this) {
                $workExperience->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SupportTicket>
     */
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
            // set the owning side to null (unless already changed)
            if ($supportTicket->getUser() === $this) {
                $supportTicket->setUser(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
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

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSkills(): array
    {
        return $this->skills;
    }

    public function setSkills(array $skills): static
    {
        $this->skills = $skills;

        return $this;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocuments(array $documents): static
    {
        $this->documents = $documents;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

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

    public function isPostulant(): bool
    {
        return $this instanceof Applicant;
    }

    public function isApplicant(): bool
    {
        return $this instanceof Applicant;
    }

    public function isRecruiter(): bool
    {
        return $this instanceof Recruiter;
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

    public function addJobOffer(JobOffer $jobOffer): self
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
     * @return Collection<int, JobOffer>
     */
    public function getFavoriteOffers(): Collection
    {
        return $this->favoriteOffers;
    }

    public function addFavoriteOffer(JobOffer $jobOffer): static
    {
        if (!$this->favoriteOffers->contains($jobOffer)) {
            $this->favoriteOffers->add($jobOffer);
        }

        return $this;
    }

    public function removeFavoriteOffer(JobOffer $jobOffer): static
    {
        $this->favoriteOffers->removeElement($jobOffer);

        return $this;
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
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(PostComment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
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

    public function hasLikedPost(Post $post): bool
    {
        foreach ($this->postLikes as $like) {
            if ($like->getPost() === $post) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function getSentFriendRequests(): Collection
    {
        return $this->sentFriendships;
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function getReceivedFriendRequests(): Collection
    {
        return $this->receivedFriendships;
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function getFriendships(): Collection
    {
        return $this->friendships;
    }

    public function isFriendWith(self $user): bool
    {
        foreach ($this->friendships as $friendship) {
            if ($friendship->getAddressee() === $user || $friendship->getRequester() === $user) {
                return true;
            }
        }

        return false;
    }

    public function hasPendingFriendRequestWith(self $user): bool
    {
        foreach ($this->sentFriendships as $request) {
            if ($request->getAddressee() === $user) {
                return true;
            }
        }

        return false;
    }

    public function addSentFriendRequest(Friendship $friendship): static
    {
        if (!$this->sentFriendships->contains($friendship)) {
            $this->sentFriendships->add($friendship);
            $friendship->setRequester($this);
        }

        return $this;
    }

    public function addReceivedFriendRequest(Friendship $friendship): static
    {
        if (!$this->receivedFriendships->contains($friendship)) {
            $this->receivedFriendships->add($friendship);
            $friendship->setAddressee($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function getReceivedRequests(): Collection
    {
        return $this->receivedFriendships;
    }

    public function initializeSocialCollections(): void
    {
        $this->initializeFriendshipCollections();
        $this->initializeContentCollections();
        $this->initializeApplicationCollections();
        $this->initializeInterviewCollections();
        $this->initializeProfileCollections();
    }

    /**
     * @return Collection<int, Hashtag>
     */
    public function getHashtags(): Collection
    {
        return $this->hashtags;
    }

    public function addHashtag(Hashtag $hashtag): static
    {
        if (!$this->hashtags->contains($hashtag)) {
            $this->hashtags->add($hashtag);
        }

        return $this;
    }

    public function removeHashtag(Hashtag $hashtag): static
    {
        $this->hashtags->removeElement($hashtag);

        return $this;
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
            // set the owning side to null (unless already changed)
            if ($interview->getRecruiter() === $this) {
                $interview->setRecruiter(null);
            }
        }

        return $this;
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
            // set the owning side to null (unless already changed)
            if ($interview->getApplicant() === $this) {
                $interview->setApplicant(null);
            }
        }

        return $this;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     */
    public function hasRole(string $role): bool
    {
        return \in_array($role, $this->getRoles(), true);
    }

    /**
     * Repository pour les amitiés, injecté pour les tests.
     *
     * @param mixed $friendshipRepository
     */
    public function setFriendshipRepository($friendshipRepository): self
    {
        $this->friendshipRepository = $friendshipRepository;

        return $this;
    }

    /**
     * Obtient le statut d'amitié avec un autre utilisateur.
     */
    public function getFriendshipStatus(self $user): ?string
    {
        // Logique pour déterminer le statut d'amitié
        return null;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFriends(): Collection
    {
        $friends = new ArrayCollection();

        foreach ($this->friendships as $friendship) {
            if ($friendship->getStatus() === 'accepted') {
                $friend = $friendship->getFriend($this);
                if ($friend) {
                    $friends->add($friend);
                }
            }
        }

        // Récupérer également les amis depuis sentFriendships et receivedFriendships
        foreach ($this->sentFriendships as $friendship) {
            if ($friendship->getStatus() === 'accepted') {
                $friends->add($friendship->getAddressee());
            }
        }

        foreach ($this->receivedFriendships as $friendship) {
            if ($friendship->getStatus() === 'accepted') {
                $friends->add($friendship->getRequester());
            }
        }

        return $friends;
    }

    private function initializeFriendshipCollections(): void
    {
        if ($this->sentFriendships === null) {
            $this->sentFriendships = new ArrayCollection();
        }
        if ($this->receivedFriendships === null) {
            $this->receivedFriendships = new ArrayCollection();
        }
        if ($this->friendships === null) {
            $this->friendships = new ArrayCollection();
        }
    }

    private function initializeContentCollections(): void
    {
        if ($this->hashtags === null) {
            $this->hashtags = new ArrayCollection();
        }
        if ($this->posts === null) {
            $this->posts = new ArrayCollection();
        }
        if ($this->comments === null) {
            $this->comments = new ArrayCollection();
        }
        if ($this->postLikes === null) {
            $this->postLikes = new ArrayCollection();
        }
    }

    private function initializeApplicationCollections(): void
    {
        if ($this->applications === null) {
            $this->applications = new ArrayCollection();
        }
        if ($this->notifications === null) {
            $this->notifications = new ArrayCollection();
        }
        if ($this->favorites === null) {
            $this->favorites = new ArrayCollection();
        }
        if ($this->subscriptions === null) {
            $this->subscriptions = new ArrayCollection();
        }
    }

    private function initializeInterviewCollections(): void
    {
        if ($this->recruiterInterviews === null) {
            $this->recruiterInterviews = new ArrayCollection();
        }
        if ($this->applicantInterviews === null) {
            $this->applicantInterviews = new ArrayCollection();
        }
    }

    private function initializeProfileCollections(): void
    {
        if ($this->education === null) {
            $this->education = new ArrayCollection();
        }
        if ($this->workExperiences === null) {
            $this->workExperiences = new ArrayCollection();
        }
        if ($this->supportTickets === null) {
            $this->supportTickets = new ArrayCollection();
        }
        if ($this->jobOffers === null) {
            $this->jobOffers = new ArrayCollection();
        }
        if ($this->favoriteOffers === null) {
            $this->favoriteOffers = new ArrayCollection();
        }
    }
}
