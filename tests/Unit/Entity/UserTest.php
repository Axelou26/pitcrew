<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\Post;
use App\Entity\JobOffer;
use App\Entity\JobApplication;
use App\Entity\Notification;
use App\Entity\Friendship;
use App\Entity\Favorite;
use App\Entity\PostLike;
use App\Entity\PostComment;
use App\Entity\RecruiterSubscription;
use App\Entity\Interview;
use App\Entity\Education;
use App\Entity\WorkExperience;
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
        $this->assertEquals('test@example.com', $this->user->getEmail());
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
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testFullName(): void
    {
        $firstName = 'John';
        $lastName = 'Doe';

        $this->user->setFirstName($firstName);
        $this->user->setLastName($lastName);

        $this->assertEquals($firstName, $this->user->getFirstName());
        $this->assertEquals($lastName, $this->user->getLastName());
        $this->assertEquals("$firstName $lastName", $this->user->getFullName());
    }

    public function testUserProfile(): void
    {
        // Test des informations du profil
        $this->user->setCompany('Test Company');
        $this->assertEquals('Test Company', $this->user->getCompany());

        $this->user->setBio('Test Bio');
        $this->assertEquals('Test Bio', $this->user->getBio());

        $this->user->setProfilePicture('profile.jpg');
        $this->assertEquals('profile.jpg', $this->user->getProfilePicture());

        $this->user->setJobTitle('Developer');
        $this->assertEquals('Developer', $this->user->getJobTitle());

        $this->user->setCity('Paris');
        $this->assertEquals('Paris', $this->user->getCity());
    }

    public function testUserSkillsAndDocuments(): void
    {
        // Test des compétences
        $skills = ['PHP', 'Symfony', 'JavaScript'];
        $this->user->setSkills($skills);
        $this->assertEquals($skills, $this->user->getSkills());

        // Test des documents
        $documents = ['cv.pdf', 'lettre.pdf'];
        $this->user->setDocuments($documents);
        $this->assertEquals($documents, $this->user->getDocuments());

        // Test du CV
        $this->user->setResume('cv.pdf');
        $this->assertEquals('cv.pdf', $this->user->getResume());
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
        // Test du rôle postulant
        $this->user->setRoles(['ROLE_USER', 'ROLE_POSTULANT']);
        $this->assertTrue($this->user->isPostulant());
        $this->assertTrue($this->user->isApplicant()); // alias de isPostulant
        $this->assertFalse($this->user->isRecruiter());

        // Test du rôle recruteur
        $this->user->setRoles(['ROLE_USER', 'ROLE_RECRUTEUR']);
        $this->assertTrue($this->user->isRecruiter());
        $this->assertFalse($this->user->isPostulant());
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
        $this->assertEquals($stripeId, $this->user->getStripeCustomerId());
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
        // On utilise un Recruiter au lieu d'un User simple
        $recruiter = new \App\Entity\Recruiter();
        $jobOffer = new JobOffer();
        $jobOffer->setTitle('Test job offer');

        // Test d'ajout d'une offre d'emploi
        $recruiter->addJobOffer($jobOffer);
        $this->assertCount(1, $recruiter->getJobOffers());
        $this->assertTrue($recruiter->getJobOffers()->contains($jobOffer));
        $this->assertSame($recruiter, $jobOffer->getRecruiter());

        // Test de suppression d'une offre d'emploi
        $recruiter->removeJobOffer($jobOffer);
        $this->assertCount(0, $recruiter->getJobOffers());
        $this->assertFalse($recruiter->getJobOffers()->contains($jobOffer));
    }

    public function testApplicationsCollection(): void
    {
        // On utilise un Applicant au lieu d'un User simple
        $applicant = new \App\Entity\Applicant();
        $application = new JobApplication();

        // Test d'ajout d'une candidature
        $applicant->addApplication($application);
        $this->assertCount(1, $applicant->getApplications());
        $this->assertTrue($applicant->getApplications()->contains($application));

        // Test de suppression d'une candidature
        $applicant->removeApplication($application);
        $this->assertCount(0, $applicant->getApplications());
        $this->assertFalse($applicant->getApplications()->contains($application));
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
        $recruiter = new \App\Entity\Recruiter();
        $subscription = new \App\Entity\RecruiterSubscription();

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
        $this->assertEquals($password, $this->user->getPassword());

        // Test de l'interface PasswordAuthenticatedUserInterface
        $this->assertInstanceOf(PasswordAuthenticatedUserInterface::class, $this->user);
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testFriendshipStatus(): void
    {
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');

        // Initialisation des collections
        $this->user->initializeSocialCollections();
        $otherUser->initializeSocialCollections();

        // Test initial - aucune relation d'amitié
        $this->assertFalse($this->user->isFriendWith($otherUser));
        $this->assertFalse($this->user->hasPendingFriendRequestWith($otherUser));

        // Création d'une demande d'amitié
        $friendRequest = new Friendship();
        $friendRequest->setRequester($this->user);
        $friendRequest->setAddressee($otherUser);
        $friendRequest->setStatus(Friendship::STATUS_PENDING);

        // Utilisation des méthodes d'ajout appropriées
        $this->user->addSentFriendRequest($friendRequest);
        $otherUser->addReceivedFriendRequest($friendRequest);

        // Vérification de la demande d'amitié en attente
        $this->assertTrue($this->user->hasPendingFriendRequestWith($otherUser));
        $this->assertFalse($this->user->isFriendWith($otherUser));

        // Acceptation de la demande d'amitié
        $friendRequest->setStatus(Friendship::STATUS_ACCEPTED);
        $this->assertTrue($this->user->isFriendWith($otherUser));
        $this->assertTrue($otherUser->isFriendWith($this->user));
    }
}
