<?php

namespace App\Controller;

use App\Entity\Parents;
use App\Entity\User;
use App\Form\ParentsType;
use App\Repository\ParentsRepository;
use App\Service\PasswordGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parents', name: 'app_parents_',)]
final class ParentsController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, ParentsRepository $parentsRepository, EntityManagerInterface $entityManager): Response
    {
        $parent = new Parents();
        $form = $this->createForm(ParentsType::class, $parent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $parent->setCreatedBy($this->getUser());
            }
            $entityManager->persist($parent);
            $entityManager->flush();
            $this->addFlash('success', 'Parent créé avec succès.');

            return $this->redirectToRoute('app_parents_index');
        }

        return $this->render('parents/index.html.twig', [
            'parents' => $parentsRepository->findBy(['deleted' => false]),
            'parent' => $parent,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parent = new Parents();
        $form = $this->createForm(ParentsType::class, $parent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $parent->setCreatedBy($this->getUser());
            }
            $entityManager->persist($parent);
            $entityManager->flush();

            $this->addFlash('success', 'Parent créé avec succès.');

            return $this->redirectToRoute('app_parents_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parents/new.html.twig', [
            'parent' => $parent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Parents $parent): Response
    {
        return $this->render('parents/show.html.twig', [
            'parent' => $parent,
        ]);
    }

    #[Route('/{id}/create-account', name: 'create_account', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createAccount(
        Request $request,
        Parents $parent,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('create-account-parent' . $parent->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($parent->getUser()) {
            $this->addFlash('warning', 'Un compte existe déjà pour ce parent.');

            return $this->accountRedirect($request, $parent->getId());
        }

        if (!$parent->getEmail()) {
            $this->addFlash('danger', 'Ajoutez une adresse e-mail avant de créer le compte.');

            return $this->accountRedirect($request, $parent->getId());
        }

        $plainPassword = $passwordGenerator->generate();
        $user = (new User())
            ->setUsername('PAR' . $parent->getId())
            ->setEmail($parent->getEmail())
            ->setFirstname($parent->getPrenom())
            ->setLastname($parent->getNom())
            ->setRoles(['ROLE_PARENT']);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        if ($this->getUser() instanceof User) {
            $user->setCreatedBy($this->getUser());
        }
        $parent->setUser($user);

        $entityManager->persist($user);
        $entityManager->flush();

        try {
            $this->sendCredentialsEmail($user, $plainPassword, $mailer, 'Création du compte parent');
            $this->addFlash('success', 'Compte parent créé et identifiants envoyés par e-mail.');
        } catch (\Throwable $exception) {
            $logger->error('Échec de l’envoi des identifiants du parent.', [
                'user_id' => $user->getId(),
                'error' => $exception->getMessage(),
            ]);
            $this->addFlash('warning', 'Compte créé, mais l’e-mail des identifiants n’a pas pu être envoyé.');
        }

        return $this->accountRedirect($request, $parent->getId());
    }

    #[Route('/{id}/reset-account', name: 'reset_account', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function resetAccount(
        Request $request,
        Parents $parent,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('reset-account-parent' . $parent->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user = $parent->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Aucun compte n’existe pour ce parent.');

            return $this->accountRedirect($request, $parent->getId());
        }

        if (!$parent->getEmail()) {
            $this->addFlash('danger', 'Ajoutez une adresse e-mail avant de réinitialiser les identifiants.');

            return $this->accountRedirect($request, $parent->getId());
        }

        $plainPassword = $passwordGenerator->generate();
        $user
            ->setEmail($parent->getEmail())
            ->setEnabled(true)
            ->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $entityManager->flush();

        try {
            $this->sendCredentialsEmail($user, $plainPassword, $mailer, 'Réinitialisation du compte parent');
            $this->addFlash('success', 'Mot de passe réinitialisé et compte activé. Les nouveaux identifiants ont été envoyés par e-mail.');
        } catch (\Throwable $exception) {
            $logger->error('Échec de l’envoi des nouveaux identifiants du parent.', [
                'user_id' => $user->getId(),
                'error' => $exception->getMessage(),
            ]);
            $this->addFlash('warning', 'Mot de passe réinitialisé et compte activé, mais l’e-mail n’a pas pu être envoyé.');
        }

        return $this->accountRedirect($request, $parent->getId());
    }

    #[Route('/{id}/close-account', name: 'close_account', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function closeAccount(Request $request, Parents $parent, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('close-account-parent' . $parent->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($parent->getUser()) {
            $parent->getUser()->setEnabled(false);
            $entityManager->flush();
            $this->addFlash('success', 'Le compte parent a été fermé.');
        } else {
            $this->addFlash('warning', 'Aucun compte n’existe pour ce parent.');
        }

        return $this->accountRedirect($request, $parent->getId());
    }

    private function accountRedirect(Request $request, int $parentId): Response
    {
        if ($request->request->getString('_return_to') === 'index') {
            return $this->redirectToRoute('app_parents_index');
        }

        return $this->redirectToRoute('app_parents_show', ['id' => $parentId]);
    }

    private function sendCredentialsEmail(User $user, string $plainPassword, MailerInterface $mailer, string $operation): void
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
                    'operation' => $operation,
                ])
        );
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Parents $parent, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParentsType::class, $parent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($parent->getUser() && $parent->getEmail()) {
                $parent->getUser()->setEmail($parent->getEmail());
            }
            $entityManager->flush();

            $this->addFlash('success', 'Parent mis à jour avec succès.');

            return $this->redirectToRoute('app_parents_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parents/edit.html.twig', [
            'parent' => $parent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Parents $parent, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $parent->getId(), $request->getPayload()->getString('_token'))) {
            $parent->setDeleted(true);
            $parent->setEnabled(false);
            $entityManager->flush();
            $this->addFlash('success', 'Parent supprimé avec succès.');
        }

        return $this->redirectToRoute('app_parents_index', [], Response::HTTP_SEE_OTHER);
    }
}
