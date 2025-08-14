<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Entity;

use App\Entity\Applicant;
use App\Entity\Education;
use App\Entity\Favorite;
use App\Entity\Friendship;
use App\Entity\Interview;
use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Entity\Notification;
use App\Entity\Post;
use App\Entity\PostComment;
use App\Entity\PostLike;
use App\Entity\Recruiter;
use App\Entity\RecruiterSubscription;
use App\Entity\User;
use App\Entity\WorkExperience;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserTest extends TestCase
{
    private User $user;
    private $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
        $this->user->initializeSocialCollections();

        // Mock du service de hachage de mot de passe
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
    }

    public function testEmailValidation(): void
    {
        // Test avec un email valide
        $this->user->setEmail('test@example.com');
        $this->assertSame('test@example.com', $this->user->getEmail());
    }

    public function testUserRoles(): void
    {
        // Par défaut, un utilisateur devrait avoir ROLE_USER
        $this->assertContains('ROLE_USER', $this->user->getRoles());

        // Test d'ajout d'un rôle
        $this->user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());

        // Test de suppression d'un rôle
        $this->user->setRoles(['ROLE_USER']);
        $this->assertNotContains('ROLE_ADMIN', $this->user->getRoles());
    }

    public function testVerification(): void
    {
        // Par défaut, un utilisateur ne devrait pas être vérifié
        $this->assertFalse($this->user->isVerified());

        // Test de vérification
        $this->user->setIsVerified(true);
        $this->assertTrue($this->user->isVerified());
    }

    public function testUserIdentifier(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);
        $this->assertSame($email, $this->user->getUserIdentifier());
    }

    public function testFullName(): void
    {
        $firstName = 'John';
        $lastName  = 'Doe';

        $this->user->setFirstName($firstName);
        $this->user->setLastName($lastName);

        $this->assertSame($firstName, $this->user->getFirstName());
        $this->assertSame($lastName, $this->user->getLastName());
        $this->assertSame("$firstName $lastName", $this->user->getFullName());
    }

    public function testUserProfile(): void
    {
        // Test des informations du profil
        $this->user->setCompany('Test Company');
        $this->assertSame('Test Company', $this->user->getCompany());

        $this->user->setBio('Test Bio');
        $this->assertSame('Test Bio', $this->user->getBio());

        $this->user->setProfilePicture('profile.jpg');
        $this->assertSame('profile.jpg', $this->user->getProfilePicture());

        $this->user->setJobTitle('Developer');
        $this->assertSame('Developer', $this->user->getJobTitle());

        $this->user->setCity('Paris');
        $this->assertSame('Paris', $this->user->getCity());
    }

    public function testUserSkillsAndDocuments(): void
    {
        // Test des compétences
        $skills = ['PHP', 'Symfony', 'JavaScript'];
        $this->user->setSkills($skills);
        $this->assertSame($skills, $this->user->getSkills());

        // Test des documents
        $documents = ['cv.pdf', 'lettre.pdf'];
        $this->user->setDocuments($documents);
        $this->assertSame($documents, $this->user->getDocuments());

        // Test du CV
        $this->user->setResume('cv.pdf');
        $this->assertSame('cv.pdf', $this->user->getResume());
    }

    public function testUserExperienceAndEducation(): void
    {
        // Test de l'éducation
        $education = new Education();
        $education->setDegree('Master en Informatique');
        $this->user->addEducation($education);
        $this->assertCount(1, $this->user->getEducation());
        $this->assertTrue($this->user->getEducation()->contains($education));

        // Test de l'expérience professionnelle
        $experience = new WorkExperience();
        $experience->setTitle('Développeur Web');
        $experience->setDescription('5 ans d\'expérience en développement web');
        $this->user->addWorkExperience($experience);
        $this->assertCount(1, $this->user->getWorkExperiences());
        $this->assertTrue($this->user->getWorkExperiences()->contains($experience));
    }

    public function testUserRoleChecks(): void
    {
        $user = new User();

        // Par défaut, l'utilisateur a le rôle ROLE_USER
        $this->assertTrue($user->hasRole('ROLE_USER'));
        $this->assertFalse($user->hasRole('ROLE_ADMIN'));
        $this->assertFalse($user->hasRole('ROLE_RECRUTEUR'));

        // Ajouter un rôle
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->assertTrue($user->hasRole('ROLE_ADMIN'));
    }

    public function testUserCreatedAt(): void
    {
        $now = new \DateTimeImmutable();
        $this->assertNotNull($this->user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->user->getCreatedAt());
    }

    public function testStripeCustomerId(): void
    {
        $stripeId = 'cus_123456789';
        $this->user->setStripeCustomerId($stripeId);
        $this->assertSame($stripeId, $this->user->getStripeCustomerId());
    }

    public function testPostsCollection(): void
    {
        $post = new Post();
        $post->setContent('Test post content');

        // Test d'ajout d'un post
        $this->user->addPost($post);
        $this->assertCount(1, $this->user->getPosts());
        $this->assertTrue($this->user->getPosts()->contains($post));
        $this->assertSame($this->user, $post->getAuthor());

        // Test de suppression d'un post
        $this->user->removePost($post);
        $this->assertCount(0, $this->user->getPosts());
        $this->assertFalse($this->user->getPosts()->contains($post));
    }

    public function testJobOffersCollection(): void
    {
        $recruiter = new Recruiter();

        // Créer un mock pour JobOffer avec setRecruiter qui retourne self
        $jobOffer = $this->createMock(JobOffer::class);
        $jobOffer->expects($this->any())
            ->method('setRecruiter')
            ->willReturnSelf();

        // Tester l'ajout
        $recruiter->addJobOffer($jobOffer);
        $this->assertTrue($recruiter->getJobOffers()->contains($jobOffer));
    }

    public function testApplicationsCollection(): void
    {
        $applicant = new Applicant();

        // Créer un mock pour Application avec setApplicant qui retourne self
        $application = $this->createMock(JobApplication::class);
        $application->expects($this->any())
            ->method('setApplicant')
            ->willReturnSelf();

        // Utiliser la réflexion pour accéder à la collection privée
        $reflection = new \ReflectionClass(Applicant::class);
        $property   = $reflection->getProperty('jobApplications');
        $property->setAccessible(true);
        $collection = new ArrayCollection();
        $property->setValue($applicant, $collection);

        // Tester l'ajout
        $applicant->addJobApplication($application);
        $this->assertTrue($collection->contains($application));
    }

    public function testNotificationsCollection(): void
    {
        $notification = new Notification();

        // Test d'ajout d'une notification
        $this->user->addNotification($notification);
        $this->assertCount(1, $this->user->getNotifications());
        $this->assertTrue($this->user->getNotifications()->contains($notification));

        // Test de suppression d'une notification
        $this->user->removeNotification($notification);
        $this->assertCount(0, $this->user->getNotifications());
        $this->assertFalse($this->user->getNotifications()->contains($notification));
    }

    public function testFriendshipCollections(): void
    {
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');

        // Initialisation des collections
        $this->user->initializeSocialCollections();
        $otherUser->initializeSocialCollections();

        // Test des demandes d'amitié envoyées
        $sentRequest = new Friendship();
        $sentRequest->setRequester($this->user);
        $sentRequest->setAddressee($otherUser);
        $sentRequest->setStatus(Friendship::STATUS_PENDING);
        $this->user->addSentFriendRequest($sentRequest);
        $otherUser->addReceivedFriendRequest($sentRequest);
        $this->assertCount(1, $this->user->getSentFriendRequests());
        $this->assertTrue($this->user->hasPendingFriendRequestWith($otherUser));

        // Test des demandes d'amitié reçues
        $receivedRequest = new Friendship();
        $receivedRequest->setRequester($otherUser);
        $receivedRequest->setAddressee($this->user);
        $receivedRequest->setStatus(Friendship::STATUS_PENDING);
        $otherUser->addSentFriendRequest($receivedRequest);
        $this->user->addReceivedFriendRequest($receivedRequest);
        $this->assertCount(1, $this->user->getReceivedRequests());
        $this->assertTrue($otherUser->hasPendingFriendRequestWith($this->user));
    }

    public function testSocialInteractions(): void
    {
        $post = new Post();
        $post->setContent('Test post');

        // Test des likes
        $like = new PostLike();
        $like->setPost($post);
        $like->setUser($this->user);
        $this->assertCount(0, $this->user->getPostLikes());
        $this->user->getPostLikes()->add($like);
        $this->assertCount(1, $this->user->getPostLikes());
        $this->assertTrue($this->user->hasLikedPost($post));

        // Test des commentaires
        $comment = new PostComment();
        $comment->setContent('Test comment');
        $this->user->addComment($comment);
        $this->assertCount(1, $this->user->getComments());
        $this->assertTrue($this->user->getComments()->contains($comment));
    }

    public function testInterviewsCollections(): void
    {
        // Test des entretiens en tant que recruteur
        $interviewAsRecruiter = new Interview();
        $this->user->addInterviewAsRecruiter($interviewAsRecruiter);
        $this->assertCount(1, $this->user->getInterviewsAsRecruiter());
        $this->user->removeInterviewAsRecruiter($interviewAsRecruiter);
        $this->assertCount(0, $this->user->getInterviewsAsRecruiter());

        // Test des entretiens en tant que candidat
        $interviewAsApplicant = new Interview();
        $this->user->addInterviewAsApplicant($interviewAsApplicant);
        $this->assertCount(1, $this->user->getInterviewsAsApplicant());
        $this->user->removeInterviewAsApplicant($interviewAsApplicant);
        $this->assertCount(0, $this->user->getInterviewsAsApplicant());
    }

    public function testSubscriptionsCollection(): void
    {
        // On utilise un Recruiter au lieu d'un User simple
        $recruiter    = new Recruiter();
        $subscription = new RecruiterSubscription();

        // Test d'ajout d'un abonnement
        $recruiter->addSubscription($subscription);
        $this->assertCount(1, $recruiter->getSubscriptions());
        $this->assertTrue($recruiter->getSubscriptions()->contains($subscription));

        // Test de suppression d'un abonnement
        $recruiter->removeSubscription($subscription);
        $this->assertCount(0, $recruiter->getSubscriptions());
        $this->assertFalse($recruiter->getSubscriptions()->contains($subscription));
    }

    public function testFavoritesCollection(): void
    {
        $favorite = new Favorite();

        // Test d'ajout d'un favori
        $this->user->addFavorite($favorite);
        $this->assertCount(1, $this->user->getFavorites());
        $this->assertTrue($this->user->getFavorites()->contains($favorite));

        // Test de suppression d'un favori
        $this->user->removeFavorite($favorite);
        $this->assertCount(0, $this->user->getFavorites());
        $this->assertFalse($this->user->getFavorites()->contains($favorite));
    }

    public function testPasswordManagement(): void
    {
        $password = 'password123';
        $this->user->setPassword($password);
        $this->assertSame($password, $this->user->getPassword());

        // Test de l'interface PasswordAuthenticatedUserInterface
        $this->assertInstanceOf(PasswordAuthenticatedUserInterface::class, $this->user);
        $this->assertSame($password, $this->user->getPassword());
    }

    public function testFriendshipStatus(): void
    {
        $user1      = new User();
        $reflection = new \ReflectionClass(User::class);
        $property   = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user1, 1);

        $user2 = new User();
        $property->setValue($user2, 2);

        // Créer un mock pour FriendshipRepository
        $friendshipRepo = $this->createMock(\App\Repository\FriendshipRepository::class);

        // Injecter le repository dans l'utilisateur
        $user1->setFriendshipRepository($friendshipRepo);

        // Vérifier le statut d'amitié (retourne null par défaut)
        $this->assertNull($user1->getFriendshipStatus($user2));
    }
}
