<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\PasswordGenerator;
use App\Service\UsernameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user', name: 'app_user_')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UsernameGenerator $usernameGenerator,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $role = $request->query->get('role');

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $this->createCredentials($user, $usernameGenerator, $passwordGenerator, $passwordHasher);
            $user->setCreatedBy($this->getUser());
            $entityManager->persist($user);
            $entityManager->flush();

            $mailSent = $this->sendCreationEmail($user, $plainPassword, $mailer, $logger);
            $this->addFlash($mailSent ? 'success' : 'warning', $mailSent ? 'Utilisateur cree avec succes et ses identifiants ont ete envoyes par e-mail.' : 'Utilisateur cree, mais le-mail des identifiants na pas pu etre envoye.');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findBySearchAndFilters($search, $role, $status),
            'form' => $form->createView(),
            'search' => $search,
            'status' => $status,
            'role' => $role,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UsernameGenerator $usernameGenerator,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $this->createCredentials($user, $usernameGenerator, $passwordGenerator, $passwordHasher);
            $user->setCreatedBy($this->getUser());
            $entityManager->persist($user);
            $entityManager->flush();

            $mailSent = $this->sendCreationEmail($user, $plainPassword, $mailer, $logger);
            $this->addFlash($mailSent ? 'success' : 'warning', $mailSent ? 'Utilisateur cree avec succes et ses identifiants ont ete envoyes par e-mail.' : 'Utilisateur cree, mais le-mail des identifiants na pas pu etre envoye.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', ['user' => $user]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur mis a jour avec succes.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', ['user' => $user, 'form' => $form]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprime avec succes.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    private function createCredentials(
        User $user,
        UsernameGenerator $usernameGenerator,
        PasswordGenerator $passwordGenerator,
        UserPasswordHasherInterface $passwordHasher,
    ): string {
        $plainPassword = $passwordGenerator->generate();
        $user->setUsername($usernameGenerator->generate($user->getFirstname() ?? '', $user->getLastname() ?? ''));
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        return $plainPassword;
    }

    private function sendCreationEmail(User $user, string $plainPassword, MailerInterface $mailer, LoggerInterface $logger): bool
    {
        try {
            $mailer->send(
                (new TemplatedEmail())
                    ->from($_ENV['SENDER_EMAIL'])
                    ->to($user->getEmail())
                    ->subject('Vos identifiants de connexion')
                    ->htmlTemplate('email/user_create.html.twig')
                    ->context([
                        'user' => $user,
                        'username' => $user->getUsername(),
                        'password' => $plainPassword,
                        'operation' => 'Creation des identifiants',
                    ])
            );

            return true;
        } catch (\Throwable $e) {
            $logger->error('Echec de l\'envoi du mail de creation utilisateur.', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}