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
use Symfony\Component\Mime\Address;

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
            $plainPassword = $this->createCredentials(
                $user,
                $usernameGenerator,
                $passwordGenerator,
                $passwordHasher
            );
            $user->setCreatedBy($this->getUser());
            $entityManager->persist($user);
            $entityManager->flush();

            $mailSent = $this->sendCreationEmail(
                $user,
                $plainPassword,
                $mailer,
                $logger
            );
            $this->addFlash(
                $mailSent ? 'success' : 'warning',
                $mailSent ? 'Utilisateur cree avec succes et ses identifiants ont ete envoyes par e-mail.' : 'Utilisateur cree, mais le-mail des identifiants na pas pu etre envoye.'
            );

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


    #[Route('/{id}/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'reset_password_' . $user->getId(),
            $request->getPayload()->getString('_token')
        )) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton CSRF invalide.'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $plainPassword = $passwordGenerator->generate();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword)
            );
            $user->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $mailSent = false;

            try {
                $mailer->send(
                    (new TemplatedEmail())
                        ->from(new Address(
                            $_ENV['SENDER_EMAIL'],
                            $_ENV['SENDER_NAME']
                        ))
                        ->to($user->getEmail())
                        ->subject('Vos nouveaux identifiants de connexion')
                        ->htmlTemplate('email/user_update.html.twig')
                        ->context([
                            'user' => $user,
                            'username' => $user->getUsername(),
                            'password' => $plainPassword,
                            'operation' => 'Réinitialisation du mot de passe',
                        ])
                );

                $mailSent = true;
            } catch (\Throwable $e) {
                $logger->error('Échec de l’envoi du mail de réinitialisation.', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->json([
                'success' => true,
                'mailSent' => $mailSent,
                'message' => $mailSent
                    ? 'Le nouveau mot de passe a été envoyé par e-mail.'
                    : 'Mot de passe renouvelé, mais l’envoi du mail a échoué.',
            ], $mailSent ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Throwable $e) {
            $logger->error('Échec de la réinitialisation du mot de passe.', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la réinitialisation.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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


    private function sendCreationEmail(
        User $user,
        string $plainPassword,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): bool {
        try {
            $context = [
                'user' => $user,
                'username' => $user->getUsername(),
                'password' => $plainPassword,
                'operation' => 'Creation des identifiants',
            ];

            // Ajouter le matricule uniquement si l'utilisateur est un élève.
            if ($user->getEleve() !== null) {
                $context['matricule'] = $user->getEleve()->getMatricule();
            }

            $mailer->send(
                (new TemplatedEmail())
                    ->from(
                        new Address(
                            $_ENV['SENDER_EMAIL'],
                            $_ENV['SENDER_NAME']
                        )
                    )
                    ->to($user->getEmail())
                    ->subject('Vos identifiants de connexion')
                    ->htmlTemplate('email/user_create.html.twig')
                    ->context($context)
            );

            return true;
        } catch (\Throwable $e) {
            $logger->error(
                "Échec de l'envoi du mail de création utilisateur.",
                [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }
}
