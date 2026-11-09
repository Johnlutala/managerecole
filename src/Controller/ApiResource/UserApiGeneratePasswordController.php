<?php

namespace App\Controller\ApiResource;

use App\Entity\User;
use App\Service\PasswordGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/users')]
final class UserApiGeneratePasswordController extends AbstractApiController
{
    #[Route('/{id}/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): JsonResponse {
        $operation = 'Reinitialisation du mot de passe';
        $authentication = $this->checkAuthentication($request);
        if (!$authentication['status']) {
            return $this->error($authentication['message'], [], $operation, $authentication['code']);
        }
        if (!array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $authentication['user']->getRoles())) {
            return $this->error('Cette opération est réservée à la direction.', [], $operation, Response::HTTP_FORBIDDEN);
        }

        try {
            $plainPassword = $passwordGenerator->generate();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setUpdatedAt(new \DateTime());

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
                'mailSent' => $mailSent,
                'message' => $mailSent
                    ? 'Le nouveau mot de passe a été envoyé par e-mail.'
                    : 'Le mot de passe a été renouvelé, mais son envoi par e-mail a échoué. Contactez le support.',
            ], $operation, $mailSent ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Throwable $e) {
            $logger->error('Échec de la réinitialisation du mot de passe.', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Une erreur est survenue',
                [],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
