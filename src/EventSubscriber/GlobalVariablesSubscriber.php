<?php

namespace App\EventSubscriber;

use App\Repository\FriendshipRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Symfony\Bundle\SecurityBundle\Security;

class GlobalVariablesSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    private Security $security;
    private FriendshipRepository $friendshipRepository;

    public function __construct(Environment $twig, Security $security, FriendshipRepository $friendshipRepository)
    {
        $this->twig = $twig;
        $this->security = $security;
        $this->friendshipRepository = $friendshipRepository;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Ajouter le nombre de demandes d'amitié en attente comme variable globale
        $user = $this->security->getUser();
        
        if ($user) {
            $pendingRequestsCount = count($this->friendshipRepository->findPendingRequestsReceived($user));
            $this->twig->addGlobal('pending_friend_requests_count', $pendingRequestsCount);
        } else {
            $this->twig->addGlobal('pending_friend_requests_count', 0);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
} 