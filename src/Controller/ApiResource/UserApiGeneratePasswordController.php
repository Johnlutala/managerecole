<?php

namespace App\Controller\ApiResource;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/rest/v1/users')]
final class UserApiGeneratePasswordController extends AbstractApiController
{

    #[Route('/{id}/generate-password', name: 'api_generate_password', methods: ['POST'])]
    public function generatePassword(
        User $user,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        Security $security,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): JsonResponse {

        $operation = "Génération des identifiants";

        try {

            // Génération d'un username unique
            $username = $this->generateUsername($user, $userRepository);
            $user->setUsername($username);

            $currentUser = $security->getUser();

            if ($user->getCreatedAt() === null) {
                $user->setCreatedAt(new \DateTime());
            }

            $user->setUpdatedAt(new \DateTime());

            if ($currentUser instanceof User) {
                $user->setCreatedBy($currentUser);
            }
            //Génération d'un mot de passe aléatoire
            $plainPassword = $this->generatePlainPassword($user);

            // Hash
            $this->hashPasswordIfNeeded(
                $user,
                $passwordHasher,
                $plainPassword
            );

            $em->flush();

            $mailSent = true;
            $start = microtime(true);
            try {
                $email = (new TemplatedEmail())
                    ->from($_ENV['SENDER_EMAIL'])
                    ->to($user->getEmail())
                    ->subject('Vos nouveaux identifiants de connexion')
                    ->htmlTemplate('email/user_create.html.twig')
                    ->context([
                        'user' => $user,
                        'username' => $username,
                        'password' => $plainPassword,
                        'operation' => 'Génération des identifiants',
                    ]);

                $mailer->send($email);
            } catch (\Exception $e) {
                $mailSent = false;
                  $this->logger->error(
                    'Échec de l’envoi du mail',
                    [
                        'user_id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'error' => $e->getMessage(),
                        'duration' => microtime(true) - $start,
                    ]
                );
            }
            return $this->success(
                [
                    'username' => $username,
                    'password' => $plainPassword,
                    'mailSent' => $mailSent
                ],
                $operation,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {

            return $this->error(
                "Une erreur est survenue",
                [$e->getMessage()],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        Security $security,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): JsonResponse {

        $operation = "Réinitialisation du mot de passe";

        try {

            // GÃ©nÃ©ration d'un nouveau mot de passe
            $plainPassword = $this->generatePlainPassword($user);

            // Ã‰crase le mot de passe actuel
            $this->hashPasswordIfNeeded(
                $user,
                $passwordHasher,
                $plainPassword
            );
            $currentUser = $security->getUser();

            $user->setUpdatedAt(new \DateTime());

            if ($currentUser instanceof User) {
                $user->setCreatedBy($currentUser);
            }
            $em->flush();

            $mailSent = true;
            try {
                $email = (new TemplatedEmail())
                    ->from($_ENV['SENDER_EMAIL'])
                    ->to($user->getEmail())
                    ->subject('Vos nouveaux identifiants de connexion')
                    ->htmlTemplate('email/user_update.html.twig')
                    ->context([
                        'user' => $user,
                        'username' => $user->getUsername(),
                        'password' => $plainPassword,
                        'operation' => 'Réinitialisation du mot de passe',
                    ]);

                $mailer->send($email);
            } catch (\Exception $e) {
                $mailSent = false;
                  $this->logger->error(
                    'Échec de l’envoi du mail',
                    [
                        'user_id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'error' => $e->getMessage(),
                    ]
                );
            }

            return $this->success(
                [
                    'username' => $user->getUsername(),
                    'password' => $plainPassword,
                    'mailSent' => $mailSent
                ],
                $operation,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {

            return $this->error(
                "Une erreur est survenue",
                [$e->getMessage()],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }



    // GÃ©nÃ¨re un username unique.
    private function generateUsername(
        User $user,
        UserRepository $userRepository
    ): string {

        $firstname = trim($user->getFirstname() ?? '');
        $lastname = trim($user->getLastname() ?? '');

        // PrÃ©nom complet en minuscule
        $first = mb_strtolower($firstname);
        // PremiÃ¨re lettre du nom en minuscule
        $last = mb_strtolower(mb_substr($lastname, 0, 1));

        do {
            // GÃ©nÃ©ration de 3 chiffres alÃ©atoires
            $number = sprintf('%02d', random_int(0, 99));

            // Exemple : john + k + 123 = johnk123
            $username = $first . $last . $number;

            // VÃ©rification de l'unicitÃ©
            $existingUser = $userRepository->findOneBy([
                'username' => $username
            ]);
        } while (
            $existingUser !== null &&
            $existingUser->getId() !== $user->getId()
        );

        return $username;
    }


    // GÃ©nÃ¨re un mot de passe.
    private function generatePlainPassword(User $user): string
    {
        // Nom complet
        $lastname = trim($user->getLastname() ?? '');

        // PrÃ©nom complet
        $firstname = trim($user->getFirstname() ?? '');

        // Nom : premiÃ¨re lettre en majuscule + reste en minuscule
        $last = ucfirst(mb_strtolower($lastname));

        // 2 premiÃ¨res lettres du prÃ©nom en majuscules
        $first = ucfirst(mb_strtolower(mb_substr($firstname, 0, 2)));
        // 3 chiffres alÃ©atoires
        $digits = sprintf('%03d', random_int(0, 999));

        // Choix du symbole
        $symbol = ['#', '@', '$'][random_int(0, 2)];

        // Exemple : Kalala + JO + 47 + @
        return $last . $first . $digits . $symbol;
    }

    //   Hash le mot de passe.

    private function hashPasswordIfNeeded(
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        string $plainPassword
    ): void {

        $user->setPassword(
            $passwordHasher->hashPassword($user, $plainPassword)
        );
    }
}
