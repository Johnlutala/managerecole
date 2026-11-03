<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Professeur;
use App\Entity\User;
use App\Repository\CoursRepository;
use App\Repository\NoteRepository;
use App\Repository\ProfesseurMessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('', name: 'app_home')]
    public function index(
        CoursRepository $coursRepository,
        NoteRepository $noteRepository,
        ProfesseurMessageRepository $messageRepository
    ): Response {
        $professeur = null;
        $coursProfesseur = [];
        $statistiquesProfesseur = ['brouillon' => 0, 'soumise' => 0, 'validee' => 0];
        $messagesRecents = [];
        $messagesNonLus = 0;
        $profilProfesseurDisponible = true;

        if ($this->isGranted('ROLE_PROFESSEUR')) {
            $user = $this->getUser();
            $professeur = $user instanceof User ? $user->getProfesseur() : null;
            $profilProfesseurDisponible = $professeur instanceof Professeur;

            if ($professeur instanceof Professeur) {
                $coursProfesseur = array_values(array_filter(
                    $coursRepository->findBy(['professeur' => $professeur], ['nom' => 'ASC']),
                    static fn (Cours $cours): bool => !$cours->isDeleted()
                        && $cours->getClasse() !== null
                        && !$cours->getClasse()->isDeleted()
                        && $cours->getClasse()->getEcole() !== null
                ));
                $statistiquesProfesseur = $noteRepository->countByProfessorAndStatus($professeur);
                $messagesRecents = $messageRepository->findBy(
                    ['professeur' => $professeur],
                    ['envoyeLe' => 'DESC'],
                    5
                );
                $messagesNonLus = $messageRepository->countUnreadFor($professeur);
            }
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'cours_professeur' => $coursProfesseur,
            'statistiques_professeur' => $statistiquesProfesseur,
            'messages_recents' => $messagesRecents,
            'messages_non_lus' => $messagesNonLus,
            'profil_professeur_disponible' => $profilProfesseurDisponible,
        ]);
    }
}
