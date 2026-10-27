<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UsernameOrMatriculeUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findForLogin(trim($identifier));

        if (!$user) {
            throw new UserNotFoundException(
                sprintf('Aucun utilisateur trouvé pour "%s".', $identifier)
            );
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(
                sprintf('Instance de "%s" non prise en charge.', get_class($user))
            );
        }

        return $this->userRepository->find($user->getId())
            ?? throw new UserNotFoundException('Utilisateur introuvable.');
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class
            || is_subclass_of($class, User::class);
    }
}
