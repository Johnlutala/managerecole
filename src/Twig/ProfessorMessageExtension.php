<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\ProfesseurMessageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ProfessorMessageExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly ProfesseurMessageRepository $messages
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('professeur_message_notifications', [$this, 'notifications']),
        ];
    }

    /**
     * @return array{non_lus: int, messages: array}
     */
    public function notifications(): array
    {
        $user = $this->security->getUser();
        $professeur = $user instanceof User ? $user->getProfesseur() : null;
        if ($professeur === null) {
            return ['non_lus' => 0, 'messages' => []];
        }

        return [
            'non_lus' => $this->messages->countUnreadFor($professeur),
            'messages' => $this->messages->findUnreadLatestFor($professeur),
        ];
    }
}
