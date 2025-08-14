<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<string, Post>
 */
class PostVoter extends Voter
{
    public const POST_EDIT   = 'POST_EDIT';
    public const POST_DELETE = 'POST_DELETE';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::POST_EDIT, self::POST_DELETE], true)
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Si l'utilisateur est un admin, il a tous les droits
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Post $post */
        $post = $subject;

        return match ($attribute) {
            self::POST_EDIT   => $this->canEdit($post, $user),
            self::POST_DELETE => $this->canDelete($post, $user),
            default           => false,
        };
    }

    private function canEdit(Post $post, UserInterface $user): bool
    {
        // Seul l'auteur du post peut le modifier
        return $user instanceof User && $post->getAuthor() === $user;
    }

    private function canDelete(Post $post, UserInterface $user): bool
    {
        // Seul l'auteur du post peut le supprimer
        return $user instanceof User && $post->getAuthor() === $user;
    }
}
