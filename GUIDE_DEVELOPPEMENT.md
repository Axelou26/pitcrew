# 🚀 Guide de Développement PitCrew

Ce guide détaille les bonnes pratiques, l'architecture et les workflows de développement pour le projet PitCrew.

## 📋 Table des Matières

1. [Architecture et Patterns](#architecture-et-patterns)
2. [Standards de Code](#standards-de-code)
3. [Workflow de Développement](#workflow-de-développement)
4. [Tests et Qualité](#tests-et-qualité)
5. [Base de Données](#base-de-données)
6. [Sécurité](#sécurité)
7. [Performance](#performance)
8. [Déploiement](#déploiement)

## 🏗️ Architecture et Patterns

### Architecture Hexagonale

PitCrew suit une architecture hexagonale (Clean Architecture) avec les couches suivantes :

```
┌─────────────────────────────────────────────────────────────┐
│                    Couche de Présentation                   │
│  Contrôleurs Symfony | Templates Twig | API Endpoints      │
├─────────────────────────────────────────────────────────────┤
│                     Couche Service                          │
│              Logique métier et orchestration               │
├─────────────────────────────────────────────────────────────┤
│                     Couche Données                          │
│              Entités Doctrine + Repositories               │
├─────────────────────────────────────────────────────────────┤
│                   Couche Infrastructure                     │
│              Services externes et configuration             │
└─────────────────────────────────────────────────────────────┘
```

### Patterns Utilisés

#### 1. Repository Pattern
```php
// src/Repository/UserRepository.php
class UserRepository extends ServiceEntityRepository
{
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%' . $role . '%')
            ->getQuery()
            ->getResult();
    }
}
```

#### 2. Service Pattern
```php
// src/Service/UserService.php
class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function createUser(User $user): void
    {
        // Logique métier
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
```

#### 3. Observer Pattern (Event Subscribers)
```php
// src/EventSubscriber/UserLifecycleSubscriber.php
class UserLifecycleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::postPersist => 'onUserCreated',
            Events::postUpdate => 'onUserUpdated',
        ];
    }

    public function onUserCreated(LifecycleEventArgs $args): void
    {
        // Logique post-création
    }
}
```

#### 4. Factory Pattern
```php
// src/Service/Factory/JobOfferFactory.php
class JobOfferFactory
{
    public function createFromDTO(JobOfferDTO $dto): JobOffer
    {
        $jobOffer = new JobOffer();
        $jobOffer->setTitle($dto->title);
        $jobOffer->setDescription($dto->description);
        // ... autres propriétés
        
        return $jobOffer;
    }
}
```

#### 5. Trait Pattern
```php
// src/Entity/Trait/FileValidationTrait.php
trait FileValidationTrait
{
    public function validateFile(UploadedFile $file): bool
    {
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        return in_array($file->getMimeType(), $allowedMimeTypes);
    }
}
```

### Principes SOLID

#### ✅ Single Responsibility Principle
```php
// ❌ Mauvais : Une classe fait trop de choses
class UserManager
{
    public function createUser() { /* ... */ }
    public function sendEmail() { /* ... */ }
    public function logActivity() { /* ... */ }
}

// ✅ Bon : Chaque classe a une responsabilité unique
class UserManager
{
    public function createUser() { /* ... */ }
}

class EmailService
{
    public function sendEmail() { /* ... */ }
}

class ActivityLogger
{
    public function logActivity() { /* ... */ }
}
```

#### ✅ Open/Closed Principle
```php
// Ouvert à l'extension, fermé à la modification
interface PaymentServiceInterface
{
    public function processPayment(float $amount): bool;
}

class StripePaymentService implements PaymentServiceInterface
{
    public function processPayment(float $amount): bool
    {
        // Implémentation Stripe
    }
}

class PayPalPaymentService implements PaymentServiceInterface
{
    public function processPayment(float $amount): bool
    {
        // Implémentation PayPal
    }
}
```

## 📝 Standards de Code

### PSR-12 Compliance

#### Formatage
```php
// ✅ Bon formatage PSR-12
class UserService
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    public function createUser(User $user): void
    {
        if (!$this->validateUser($user)) {
            throw new InvalidUserException('User validation failed');
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function validateUser(User $user): bool
    {
        return !empty($user->getEmail()) && !empty($user->getPassword());
    }
}
```

#### Naming Conventions
```php
// ✅ Classes : PascalCase
class JobOfferService
class UserRepository
class EmailNotificationService

// ✅ Méthodes : camelCase
public function createUser()
public function findByEmail()
public function sendNotification()

// ✅ Variables : camelCase
private string $userRepository;
private array $validUsers;
private bool $isActive;

// ✅ Constantes : UPPER_SNAKE_CASE
public const MAX_LOGIN_ATTEMPTS = 3;
public const DEFAULT_PAGE_SIZE = 20;
```

### Documentation des Méthodes

```php
/**
 * Crée un nouvel utilisateur avec validation
 *
 * @param User $user L'utilisateur à créer
 * @param bool $sendWelcomeEmail Envoyer un email de bienvenue
 *
 * @throws InvalidUserException Si la validation échoue
 * @throws DuplicateUserException Si l'email existe déjà
 *
 * @return User L'utilisateur créé avec ID
 */
public function createUser(User $user, bool $sendWelcomeEmail = true): User
{
    // Implémentation
}
```

## 🔄 Workflow de Développement

### 🌿 Stratégie de Branches Git

```
production (production)
├── pré-prod (pré-production)
│   └── dev (développement)
│       ├── feature/user-authentication
│       ├── feature/job-matching
│       └── feature/payment-integration
```

#### Types de Branches

1. **`production`** : Code en production, toujours stable
2. **`pré-prod`** : Code en pré-production, tests finaux
3. **`dev`** : Branche de développement principale
4. **`feature/*`** : Nouvelles fonctionnalités
5. **`hotfix/*`** : Corrections urgentes en production
6. **`release/*`** : Préparation des releases

### 📋 Workflow de Feature

```bash
# 1. Créer une branche feature
git checkout dev
git pull origin dev
git checkout -b feature/nouvelle-fonctionnalite

# 2. Développer et commiter
git add .
git commit -m "feat: implémentation de la nouvelle fonctionnalité"
git commit -m "test: ajout des tests unitaires"
git commit -m "docs: mise à jour de la documentation"

# 3. Pousser et créer PR
git push origin feature/nouvelle-fonctionnalite
# Créer Pull Request vers dev sur GitHub
```

### 🚀 Messages de Commit Conventionnels

```bash
# Format : type(scope): description

# Types principaux
feat: nouvelle fonctionnalité
fix: correction de bug
docs: mise à jour documentation
style: formatage du code
refactor: refactorisation
test: ajout de tests
chore: tâches de maintenance

# Exemples
feat(auth): ajout de l'authentification à deux facteurs
fix(user): correction du bug de validation email
docs(api): mise à jour de la documentation des endpoints
style: application des standards PSR-12
refactor(service): refactorisation du service de paiement
test(repository): ajout des tests pour UserRepository
chore(deps): mise à jour des dépendances
```

### 🔄 Code Review Process

#### Checklist de Review

- [ ] **Fonctionnalité** : Le code fait ce qui est attendu ?
- [ ] **Tests** : Tests unitaires et d'intégration ajoutés ?
- [ ] **Documentation** : Code et API documentés ?
- [ ] **Standards** : Respect des standards PSR-12 ?
- [ ] **Sécurité** : Pas de vulnérabilités de sécurité ?
- [ ] **Performance** : Pas de problèmes de performance ?
- [ ] **Maintenabilité** : Code lisible et maintenable ?

#### Commentaires de Review

```php
// ❌ Commentaire inutile
// Incrémente le compteur
$counter++;

// ✅ Commentaire utile
// Incrémente le compteur de tentatives de connexion
// Utilisé pour la limitation de taux (rate limiting)
$loginAttemptsCounter++;
```

## 🧪 Tests et Qualité

### 🧪 Structure des Tests

```
tests/
├── Unit/                    # Tests unitaires
│   ├── Service/
│   ├── Entity/
│   └── Repository/
├── Integration/             # Tests d'intégration
│   ├── Controller/
│   └── Service/
├── Functional/              # Tests fonctionnels
│   └── Controller/
└── Performance/             # Tests de performance
    └── HomepagePerformanceTest.php
```

### 🧪 Tests Unitaires

#### Exemple de Test Unitaire
```php
// tests/Unit/Service/UserServiceTest.php
class UserServiceTest extends TestCase
{
    private UserService $userService;
    private MockObject $userRepository;
    private MockObject $entityManager;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->userService = new UserService(
            $this->userRepository,
            $this->entityManager
        );
    }

    public function testCreateUserWithValidData(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->userService->createUser($user);

        // Assert
        $this->assertNotNull($user->getId());
    }

    public function testCreateUserWithInvalidEmail(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('invalid-email');
        $user->setPassword('password123');

        // Act & Assert
        $this->expectException(InvalidUserException::class);
        $this->userService->createUser($user);
    }
}
```

### 🔍 Tests d'Intégration

```php
// tests/Integration/Service/UserServiceIntegrationTest.php
class UserServiceIntegrationTest extends BaseTestCase
{
    public function testCreateUserPersistsToDatabase(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('integration@example.com');
        $user->setPassword('password123');

        // Act
        $this->userService->createUser($user);

        // Assert
        $persistedUser = $this->userRepository->findByEmail('integration@example.com');
        $this->assertNotNull($persistedUser);
        $this->assertEquals('integration@example.com', $persistedUser->getEmail());
    }
}
```

### 🚀 Tests de Performance

```php
// tests/Performance/HomepagePerformanceTest.php
class HomepagePerformanceTest extends WebTestCase
{
    public function testHomepageLoadsUnder500ms(): void
    {
        $client = static::createClient();
        
        $startTime = microtime(true);
        
        $client->request('GET', '/');
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // en millisecondes
        
        $this->assertLessThan(500, $loadTime, 
            "La page d'accueil doit se charger en moins de 500ms, temps actuel: {$loadTime}ms");
        
        $this->assertResponseIsSuccessful();
    }
}
```

### 🔍 Qualité du Code

#### PHPStan (Analyse Statique)
```bash
# Analyse complète
composer phpstan

# Configuration dans phpstan.neon
parameters:
    level: 8
    paths:
        - src/
    excludePaths:
        - src/DataFixtures/
```

#### PHP CS Fixer (Standards de Code)
```bash
# Vérifier les standards
composer php-cs-fixer -- --dry-run

# Corriger automatiquement
composer php-cs-fixer
```

#### PHPMD (Détection de Problèmes)
```bash
# Analyser le code
composer phpmd

# Configuration dans phpmd.xml.dist
<?xml version="1.0"?>
<ruleset name="PHPMD rule set"
         xmlns="http://pmd.sf.net/ruleset/1.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://pmd.sf.net/ruleset/1.0.0
         http://pmd.sf.net/ruleset_xml_schema.xsd"
         xsi:noNamespaceSchemaLocation="
         http://pmd.sf.net/ruleset_xml_schema.xsd">
    <rule ref="rulesets/cleancode.xml"/>
    <rule ref="rulesets/codesize.xml"/>
    <rule ref="rulesets/controversial.xml"/>
    <rule ref="rulesets/design.xml"/>
    <rule ref="rulesets/naming.xml"/>
    <rule ref="rulesets/unusedcode.xml"/>
</ruleset>
```

## 🗄️ Base de Données

### 📊 Conception des Entités

#### Relations et Contraintes
```php
// src/Entity/User.php
class User
{
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

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Post::class, orphanRemoval: true)]
    private Collection $posts;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'user_friends')]
    private Collection $friends;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->friends = new ArrayCollection();
    }
}
```

#### Migrations
```bash
# Créer une migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Vérifier le statut
php bin/console doctrine:migrations:status

# Annuler la dernière migration
php bin/console doctrine:migrations:migrate prev
```

### 🔍 Optimisation des Requêtes

#### Utilisation des Index
```php
// src/Entity/User.php
#[ORM\Table(name: '`user`')]
#[ORM\Index(columns: ['email'], name: 'idx_user_email')]
#[ORM\Index(columns: ['created_at'], name: 'idx_user_created_at')]
class User
{
    // ...
}
```

#### Requêtes Optimisées
```php
// ❌ Mauvais : N+1 queries
$users = $this->userRepository->findAll();
foreach ($users as $user) {
    $posts = $user->getPosts(); // Requête supplémentaire
}

// ✅ Bon : Eager loading
$users = $this->userRepository->findAllWithPosts();
// Requête unique avec JOIN
```

#### Pagination
```php
// src/Repository/UserRepository.php
public function findPaginated(int $page = 1, int $limit = 20): Paginator
{
    $query = $this->createQueryBuilder('u')
        ->orderBy('u.createdAt', 'DESC')
        ->getQuery();

    return new Paginator($query, false);
}
```

## 🔒 Sécurité

### 🛡️ Validation des Entrées

#### Validation des Formulaires
```php
// src/Form/UserType.php
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire']),
                    new Email(['message' => 'Format d\'email invalide']),
                    new UniqueEntity([
                        'entityClass' => User::class,
                        'field' => 'email',
                        'message' => 'Cet email est déjà utilisé'
                    ])
                ]
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe est obligatoire']),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ]);
    }
}
```

#### Validation des Entités
```php
// src/Entity/User.php
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'Format d\'email invalide')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre'
    )]
    private ?string $password = null;
}
```

### 🔐 Authentification et Autorisation

#### Voters Personnalisés
```php
// src/Security/Voter/PostVoter.php
class PostVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Post && in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $post = $subject;

        if (!$user instanceof User) {
            return false;
        }

        return match($attribute) {
            self::VIEW => $this->canView($post, $user),
            self::EDIT => $this->canEdit($post, $user),
            self::DELETE => $this->canDelete($post, $user),
            default => false,
        };
    }

    private function canEdit(Post $post, User $user): bool
    {
        return $post->getAuthor() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }
}
```

#### Protection CSRF
```php
// config/packages/framework.yaml
framework:
    csrf_protection: true
    form:
        csrf_protection: true
        csrf_field_name: _token
        csrf_token_id: form
```

### 🚫 Protection contre les Attaques

#### Rate Limiting
```php
// src/Controller/SecurityController.php
use Symfony\Component\RateLimiter\RateLimiterFactory;

class SecurityController extends AbstractController
{
    public function login(Request $request, RateLimiterFactory $loginLimiter): Response
    {
        $limiter = $loginLimiter->create($request->getClientIp());
        
        if (false === $limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException('Trop de tentatives de connexion');
        }

        // Logique de connexion
    }
}
```

#### Headers de Sécurité
```yaml
# config/packages/security.yaml
security:
    # ...
    firewalls:
        main:
            # ...
            access_control:
                - { path: ^/admin, roles: ROLE_ADMIN }
                - { path: ^/api, roles: ROLE_USER }
```

## 📈 Performance

### 🚀 Optimisation du Cache

#### Configuration Redis
```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'
        system: cache.adapter.system
        directory: '%kernel.cache_dir%/pools'
        pools:
            cache.doctrine.orm.default.result:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_URL)%'
                default_lifetime: 3600
```

#### Cache des Requêtes
```php
// src/Repository/UserRepository.php
class UserRepository extends ServiceEntityRepository
{
    public function findActiveUsers(): array
    {
        $cacheKey = 'active_users';
        $cache = $this->getEntityManager()->getConfiguration()->getQueryCacheImpl();
        
        if ($cached = $cache->fetch($cacheKey)) {
            return $cached;
        }

        $users = $this->createQueryBuilder('u')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $cache->save($cacheKey, $users, 3600); // Cache 1 heure
        
        return $users;
    }
}
```

### 🗄️ Optimisation des Requêtes

#### Utilisation des Index
```sql
-- Ajouter des index pour améliorer les performances
CREATE INDEX idx_user_email ON user(email);
CREATE INDEX idx_user_created_at ON user(created_at);
CREATE INDEX idx_post_author_created ON post(author_id, created_at);
```

#### Requêtes avec JOIN
```php
// src/Repository/PostRepository.php
public function findPostsWithAuthorAndComments(int $limit = 20): array
{
    return $this->createQueryBuilder('p')
        ->select('p', 'a', 'c')
        ->leftJoin('p.author', 'a')
        ->leftJoin('p.comments', 'c')
        ->orderBy('p.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

### 📊 Monitoring des Performances

#### Métriques Prometheus
```php
// src/Service/MetricsCollector.php
class MetricsCollector
{
    private Counter $httpRequestsTotal;
    private Histogram $httpRequestDuration;

    public function __construct(PrometheusRegistry $registry)
    {
        $this->httpRequestsTotal = $registry->getOrRegisterCounter('app', 'http_requests_total', 'Total HTTP requests');
        $this->httpRequestDuration = $registry->getOrRegisterHistogram('app', 'http_request_duration_seconds', 'HTTP request duration');
    }

    public function recordRequest(string $method, string $route, int $statusCode, float $duration): void
    {
        $this->httpRequestsTotal->inc(['method' => $method, 'route' => $route, 'status_code' => $statusCode]);
        $this->httpRequestDuration->observe($duration);
    }
}
```

## 🚀 Déploiement

### 🌍 Environnements

#### Configuration par Environnement
```yaml
# config/packages/framework.yaml
framework:
    cache:
        app: '%env(CACHE_ADAPTER)%'
    profiler:
        collect: '%env(COLLECT_PROFILER)%'
    debug: '%env(DEBUG)%'

# .env.local (développement)
CACHE_ADAPTER=cache.adapter.filesystem
COLLECT_PROFILER=true
DEBUG=true

# .env.prod (production)
CACHE_ADAPTER=cache.adapter.redis
COLLECT_PROFILER=false
DEBUG=false
```

#### Variables d'Environnement Sensibles
```bash
# .env.local (ne pas commiter)
DATABASE_URL="mysql://user:password@localhost:3306/pitcrew"
REDIS_URL="redis://localhost:6379"
APP_SECRET="votre-secret-tres-securise"
STRIPE_SECRET_KEY="sk_test_..."
JITSI_APP_ID="votre-app-id"
```

### 🐳 Déploiement Docker

#### Scripts de Déploiement
```bash
# Démarrage des environnements
./manage-environments.sh dev      # Développement
./manage-environments.sh preprod  # Pré-production
./manage-environments.sh prod     # Production

# Gestion
./manage-environments.sh status   # Statut des services
./manage-environments.sh logs     # Logs en temps réel
./manage-environments.sh clean    # Nettoyer tout
```

#### Configuration Production
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  app:
    environment:
      - APP_ENV=prod
      - APP_DEBUG=0
      - APP_SECRET=${APP_SECRET}
    volumes:
      - ./docker/nginx/ssl:/etc/nginx/ssl:ro
    ports:
      - "80:80"
      - "443:443"
```

### 🔄 CI/CD avec GitHub Actions

#### Workflow de Déploiement
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: production
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Run tests
        run: composer test:all
        
      - name: Deploy to production
        run: |
          # Script de déploiement
          ./deploy.sh production
```

## 📚 Ressources et Références

### 🔗 Liens Utiles

- **Symfony Documentation** : https://symfony.com/doc/current/
- **Doctrine ORM** : https://www.doctrine-project.org/projects/orm.html
- **PHPStan** : https://phpstan.org/
- **PHP CS Fixer** : https://cs.symfony.com/
- **PHPMD** : https://phpmd.org/

### 📖 Livres Recommandés

- "Clean Code" par Robert C. Martin
- "Design Patterns" par Gang of Four
- "Domain-Driven Design" par Eric Evans
- "Refactoring" par Martin Fowler

### 🎯 Bonnes Pratiques Résumées

1. **Code** : Suivre PSR-12, utiliser les patterns appropriés
2. **Tests** : Couverture > 80%, tests unitaires et d'intégration
3. **Sécurité** : Validation des entrées, protection CSRF, rate limiting
4. **Performance** : Cache Redis, index BDD, requêtes optimisées
5. **Documentation** : Code documenté, README à jour
6. **Git** : Messages conventionnels, branches feature, code review

---

**🎉 Ce guide vous accompagne dans le développement de PitCrew !**

**🚀 Pour commencer : `./manage-environments.sh dev`**
