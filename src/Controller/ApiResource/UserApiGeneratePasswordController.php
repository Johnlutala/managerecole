<?php

namespace App\Controller\ApiResource;

use App\Entity\User;
use App\Service\PasswordGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/users')]
final class UserApiGeneratePasswordController extends AbstractApiController
{
    #[Route('/{id}/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        EntityManagerInterface $em,
        Security $security,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): JsonResponse {
        $operation = 'Reinitialisation du mot de passe';

        try {
            $plainPassword = $passwordGenerator->generate();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setUpdatedAt(new \DateTime());

            $currentUser = $security->getUser();
            if ($currentUser instanceof User) {
                $user->setCreatedBy($currentUser);
            }

            $em->flush();

            $mailSent = true;
            try {
                $mailer->send(
                    (new TemplatedEmail())
                        ->from($_ENV['SENDER_EMAIL'])
                        ->to($user->getEmail())
                        ->subject('Vos nouveaux identifiants de connexion')
                        ->htmlTemplate('email/user_update.html.twig')
                        ->context([
                            'user' => $user,
                            'username' => $user->getUsername(),
                            'password' => $plainPassword,
                            'operation' => $operation,
                        ])
                );
            } catch (\Throwable $e) {
                $mailSent = false;
                $logger->error('Echec de l\'envoi du mail de reinitialisation.', [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->success([
                'username' => $user->getUsername(),
                'password' => $plainPassword,
                'mailSent' => $mailSent,
            ], $operation, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->error(
                'Une erreur est survenue',
                [$e->getMessage()],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}