<?php

namespace App\Controller;

use App\Repository\ParticipantRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Repository\WorkshopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{

#[Route('/', name: 'app_default')]
public function index(
    WorkshopRepository $workshopRepository,
    ParticipantRepository $participantRepository,
    UserRepository $userRepository
): Response {

    $user = $this->getUser();

    if (!$user instanceof User) {
        throw $this->createAccessDeniedException();
    }

    if ($this->isGranted('ROLE_ADMIN')) {

        // L'administrateur voit toutes les statistiques
        $totalWorkshops = $workshopRepository->count([
            'deleted' => false,
        ]);

        $totalParticipants = $participantRepository->count([
            'deleted' => false,
        ]);

        $totalUsers = $userRepository->count([
            'deleted' => false,
        ]);

    } else {

        // L'utilisateur ne voit que ses propres données
        $totalWorkshops = count($workshopRepository->findAllNotDeletedByUser($user));

        $totalParticipants = count($participantRepository->findAllNotDeletedByUser($user));

        // Un utilisateur ne peut voir que son propre compte
        $totalUsers = 1;
    }

    return $this->render('default/index.html.twig', [
        'controller_name' => 'DefaultController',
        'totalWorkshops' => $totalWorkshops,
        'totalParticipants' => $totalParticipants,
        'totalUsers' => $totalUsers,
    ]);
}
}
