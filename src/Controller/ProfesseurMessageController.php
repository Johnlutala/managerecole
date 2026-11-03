<?php

namespace App\Controller;

use App\Entity\MessageProfesseur;
use App\Entity\Professeur;
use App\Entity\User;
use App\Repository\ProfesseurMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/professeur/messages', name: 'app_professeur_messages_')]
#[IsGranted('ROLE_PROFESSEUR')]
final class ProfesseurMessageController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ProfesseurMessageRepository $messages): Response
    {
        $professeur = $this->professeurConnecte();

        return $this->render('professeur/messages/index.html.twig', [
            'messages' => $messages->findBy(['professeur' => $professeur], ['envoyeLe' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        MessageProfesseur $message,
        EntityManagerInterface $entityManager
    ): Response {
        if ($message->getProfesseur()?->getId() !== $this->professeurConnecte()->getId()) {
            throw $this->createNotFoundException();
        }

        if ($message->getLuLe() === null) {
            $message->setLuLe(new \DateTimeImmutable());
            $entityManager->flush();
        }

        return $this->render('professeur/messages/show.html.twig', ['message' => $message]);
    }

    private function professeurConnecte(): Professeur
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getProfesseur() instanceof Professeur) {
            throw $this->createAccessDeniedException('Aucun profil professeur n’est associé à ce compte.');
        }

        return $user->getProfesseur();
    }
}
