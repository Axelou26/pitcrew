<?php

namespace App\EventSubscriber;

use App\Repository\FriendshipRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\UserRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GlobalVariablesSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    private Security $security;
    private FriendshipRepository $friendshipRepository;
    private UserRepository $userRepository;
    private CacheInterface $cache;

    public function __construct(
        Environment $twig,
        Security $security,
        FriendshipRepository $friendshipRepository,
        UserRepository $userRepository,
        CacheInterface $cache
    ) {
        $this->twig = $twig;
        $this->security = $security;
        $this->friendshipRepository = $friendshipRepository;
        $this->userRepository = $userRepository;
        $this->cache = $cache;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Ne s'exécuter que pour les requêtes principales (pas les sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        // Vérifier si le contrôleur a besoin des variables globales
        $controller = $event->getController();
        if (is_array($controller)) {
            $controllerClass = get_class($controller[0]);
            $method = $controller[1];
        } else {
            $controllerClass = get_class($controller);
            $method = '__invoke';
        }

        // Liste des contrôleurs qui ont besoin des variables globales
        $neededControllers = [
            'App\Controller\DashboardController',
            'App\Controller\HomeController',
            'App\Controller\ProfileController',
            'App\Controller\FriendshipController',
            'App\Controller\MessageController',
        ];

        // Si ce n'est pas un contrôleur qui a besoin des variables, on sort
        if (!in_array($controllerClass, $neededControllers)) {
            return;
        }

        $user = $this->security->getUser();
        $pendingRequestsCount = 0;

        if ($user) {
            // Utiliser le cache pour éviter les requêtes répétées
            $cacheKey = 'pending_friend_requests_' . $user->getId();
            $pendingRequestsCount = $this->cache->get($cacheKey, function (ItemInterface $item) use ($user) {
                $item->expiresAfter(300); // Cache pour 5 minutes
                // Utiliser la méthode optimisée qui fait un COUNT au lieu de récupérer tous les objets
                return $this->friendshipRepository->countPendingRequestsReceived($user);
            });
        }

        $this->twig->addGlobal('pending_friend_requests_count', $pendingRequestsCount);
        $this->twig->addGlobal('userRepository', $this->userRepository);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
