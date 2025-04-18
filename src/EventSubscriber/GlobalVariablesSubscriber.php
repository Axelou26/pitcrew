<?php

namespace App\EventSubscriber;

use App\Repository\FriendshipRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\UserRepository;

class GlobalVariablesSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    private Security $security;
    private FriendshipRepository $friendshipRepository;
    private UserRepository $userRepository;

    public function __construct(
        Environment $twig,
        Security $security,
        FriendshipRepository $friendshipRepository,
        UserRepository $userRepository
    ) {
        $this->twig = $twig;
        $this->security = $security;
        $this->friendshipRepository = $friendshipRepository;
        $this->userRepository = $userRepository;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $user = $this->security->getUser();
        $pendingRequestsCount = 0;

        if ($user) {
            $pendingRequestsCount = count($this->friendshipRepository->findByPendingRequestsReceived($user));
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
