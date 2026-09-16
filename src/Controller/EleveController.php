<?php

namespace App\Controller;

use App\Entity\Eleve;
use App\Entity\User;
use App\Form\EleveType;
use App\Repository\EleveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\PasswordGenerator;
use Psr\Log\LoggerInterface;

#[Route('/eleve', name: 'app_eleve_')]
final class EleveController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, EleveRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $eleve = new Eleve();
        $form = $this->createForm(EleveType::class, $eleve);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $eleve->setCreatedBy($this->getUser());
            }
            $entityManager->persist($eleve);
            $entityManager->flush();
            $this->addFlash('success', 'Élève créé(e) avec succès.');
            return $this->redirectToRoute('app_eleve_index');
        }
        return $this->render('eleve/index.html.twig', ['eleve' => $eleve, 'eleves' => $repository->findAll(), 'form' => $form->createView()]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $eleve = new Eleve();
        $form = $this->createForm(EleveType::class, $eleve);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $eleve->setCreatedBy($this->getUser());
            }
            $entityManager->persist($eleve);
            $entityManager->flush();
            $this->addFlash('success', 'Élève créé(e) avec succès.');
            return $this->redirectToRoute('app_eleve_index');
        }
        return $this->render('eleve/new.html.twig', ['eleve' => $eleve, 'form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Eleve $eleve): Response
    {
        return $this->render('eleve/show.html.twig', [
            'eleve' => $eleve,
            'iscompte' => $eleve->getUser() !== null,
        ]);
    }

    #[Route('/{id}/create-account', name: 'create_account', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createAccount(
        Request $request,
        Eleve $eleve,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('create-account' . $eleve->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($eleve->getUser() !== null) {
            $this->addFlash('warning', 'Un compte existe déjà pour cet élève.');

            return $this->redirectToRoute('app_eleve_show', ['id' => $eleve->getId()]);
        }

        if (!$eleve->getEmail()) {
            $this->addFlash('danger', 'Impossible de créer le compte: l’élève ne possède pas d’adresse e-mail.');

            return $this->redirectToRoute('app_eleve_show', ['id' => $eleve->getId()]);
        }

        $plainPassword = $passwordGenerator->generate();
        $user = (new User())
            ->setUsername($eleve->getMatricule())
            ->setEmail($eleve->getEmail())
            ->setFirstname($eleve->getPrenom())
            ->setLastname($eleve->getNom())
            ->setRoles(['ROLE_ELEVE']);
        $eleve->setUser($user);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $user->setCreatedBy($this->getUser());

        $entityManager->persist($user);
        $entityManager->flush();

        try {
            $mailer->send(
                (new TemplatedEmail())
                    ->from(new Address($_ENV['SENDER_EMAIL'], $_ENV['SENDER_NAME']))
                    ->to($user->getEmail())
                    ->subject('Vos identifiants de connexion')
                    ->htmlTemplate('email/user_create.html.twig')
                    ->context([
                        'user' => $user,
                        'username' => $user->getUsername(),
                        'password' => $plainPassword,
                        'operation' => 'Création du compte élève',
                    ])
            );
            $this->addFlash('success', 'Compte élève créé et identifiants envoyés par e-mail.');
        } catch (\Throwable $exception) {
            $logger->error('Échec de l’envoi du mail du compte élève.', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $exception->getMessage(),
            ]);
            $this->addFlash('warning', 'Compte élève créé, mais l’e-mail n’a pas pu être envoyé.');
        }

        return $this->redirectToRoute('app_eleve_show', ['id' => $eleve->getId()]);
    }

    #[Route('/{id}/reset-account', name: 'reset_account', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function resetAccount(
        Request $request,
        Eleve $eleve,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('reset-account' . $eleve->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user = $eleve->getUser();
        if ($user === null) {
            $this->addFlash('warning', 'Aucun compte n’existe pour cet élève.');

            return $this->redirectToRoute('app_eleve_show', ['id' => $eleve->getId()]);
        }

        $plainPassword = $passwordGenerator->generate();
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $entityManager->flush();

        try {
            $this->sendCredentialsEmail($user, $plainPassword, $mailer);
            $this->addFlash('success', 'Mot de passe réinitialisé et nouveaux identifiants envoyés par e-mail.');
        } catch (\Throwable $exception) {
            $logger->error('Échec de l’envoi du mail de réinitialisation du compte élève.', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $exception->getMessage(),
            ]);
            $this->addFlash('warning', 'Mot de passe réinitialisé, mais l’e-mail n’a pas pu être envoyé.');
        }

        return $this->redirectToRoute('app_eleve_show', ['id' => $eleve->getId()]);
    }

    private function sendCredentialsEmail(User $user, string $plainPassword, MailerInterface $mailer): void
    {
        $mailer->send(
            (new TemplatedEmail())
                ->from(new Address($_ENV['SENDER_EMAIL'], $_ENV['SENDER_NAME']))
                ->to($user->getEmail())
                ->subject('Vos identifiants de connexion')
                ->htmlTemplate('email/user_create.html.twig')
                ->context([
                    'user' => $user,
                    'username' => $user->getUsername(),
                    'password' => $plainPassword,
                    'operation' => 'Gestion des identifiants élève',
                ])
        );
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Eleve $eleve, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EleveType::class, $eleve);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Élève mis(e) à jour avec succès.');
            return $this->redirectToRoute('app_eleve_index');
        }
        return $this->render('eleve/edit.html.twig', ['eleve' => $eleve, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Eleve $eleve, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $eleve->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($eleve);
            $entityManager->flush();
            $this->addFlash('success', 'Élève supprimé(e) avec succès.');
        }
        return $this->redirectToRoute('app_eleve_index');
    }
}
