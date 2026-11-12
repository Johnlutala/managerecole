<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Professeur;
use App\Entity\Eleve;
use App\Entity\Parents;
use App\Service\PasswordGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account', name: 'app_account_')]
#[IsGranted('ROLE_ADMIN')]
final class AccountController extends AbstractController
{
    #[Route('/{type}/{id}/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        string $type,
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        PasswordGenerator $passwordGenerator,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): JsonResponse {

        if (!$this->isCsrfTokenValid(
            'reset_password_' . $type . '_' . $id,
            $request->getPayload()->getString('_token')
        )) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton de sécurité invalide. Veuillez actualiser la page.',
            ], Response::HTTP_FORBIDDEN);
        }

        $entities = [
            'professeur' => Professeur::class,
            'eleve' => Eleve::class,
            'parent' => Parents::class,
            'user' => User::class,
        ];

        if (!isset($entities[$type])) {
            return $this->json([
                'success' => false,
                'message' => 'Type de compte non reconnu.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $account = $em->getRepository($entities[$type])->find($id);

            if (!$account) {
                return $this->json([
                    'success' => false,
                    'message' => 'Compte introuvable.',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($type === 'user') {
                $user = $account;
            } else {
                if (!method_exists($account, 'getUser')) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Cette entité ne possède pas de relation vers un utilisateur.',
                    ], Response::HTTP_BAD_REQUEST);
                }

                $user = $account->getUser();
            }

            if (!$user instanceof User) {
                return $this->json([
                    'success' => false,
                    'message' => 'Aucun compte utilisateur associé à cet enregistrement.',
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$user->getEmail()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Aucune adresse e-mail n’est renseignée.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Génération du nouveau mot de passe
            $plainPassword = $passwordGenerator->generate();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword)
            );

            $user->setUpdatedAt(new \DateTime());

            $em->flush();

            // Envoi du mail
            $mailSent = false;

            try {
                $senderEmail = $_ENV['SENDER_EMAIL'] ?? null;
                $senderName = $_ENV['SENDER_NAME'] ?? '';

                if (!$senderEmail) {
                    throw new \RuntimeException('La variable SENDER_EMAIL est absente.');
                }

                $email = (new TemplatedEmail())
                    ->from(new Address($senderEmail, $senderName))
                    ->to($user->getEmail())
                    ->subject('Vos nouveaux identifiants de connexion')
                    ->htmlTemplate('email/user_update.html.twig')
                    ->context([
                        'user' => $user,
                        'username' => $user->getUserIdentifier(),
                        'password' => $plainPassword,
                        'operation' => 'Réinitialisation du mot de passe',
                    ]);

                $mailer->send($email);

                $mailSent = true;
            } catch (\Throwable $e) {
                $logger->error('Échec de l’envoi du mail.', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                ]);
            }

            // Réponse envoyée au JavaScript
            return $this->json([
                'success' => true,
                'mailSent' => $mailSent,
                'username' => $user->getUserIdentifier(),
                'password' => $plainPassword,
                'message' => $mailSent
                    ? 'Les identifiants ont été envoyés par e-mail.'
                    : 'Le mot de passe a été modifié, mais le courriel n’a pas pu être envoyé.',
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {

            $logger->error('Erreur lors de la réinitialisation du mot de passe.', [
                'type' => $type,
                'account_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la réinitialisation.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
