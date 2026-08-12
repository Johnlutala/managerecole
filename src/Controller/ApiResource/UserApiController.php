<?php

namespace App\Controller\ApiResource;

use App\Dto\Api\UserCreationPayload;
use App\Entity\User;
use App\Repository\MerchantConfigurationRepository;
use App\Repository\UserRepository;
use App\Service\PasswordGenerator;
use App\Service\UsernameGenerator;
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
final class UserApiController extends AbstractApiController
{
    public function __construct(private readonly UserRepository $repo)
    {
    }

    #[Route('', name: 'api_get_enabled_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        $operation = 'Recuperation des utilisateurs';

        try {
            $users = $this->repo->findBy(['enabled' => true, 'deleted' => false]);
            $data = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);

            return $this->success(json_decode($data, true), $operation, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->error('Une erreur est survenue', [$e->getMessage()], $operation, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_create_user', methods: ['POST'])]
    public function createUser(
        Request $request,
        MerchantConfigurationRepository $mcrepo,
        UsernameGenerator $usernameGenerator,
        PasswordGenerator $passwordGenerator,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): JsonResponse {
        $operation = 'Creation utilisateur';

        try {
            $data = $this->getJsonData($request);
            $validationMessages = $this->validateObject(new UserCreationPayload(
                $data['firstname'] ?? '',
                $data['lastname'] ?? '',
                $data['email'] ?? '',
                $data['merchant'] ?? '',
            ));

            if (count($validationMessages) > 0) {
                return $this->error('Donnees invalides', $validationMessages, $operation, Response::HTTP_BAD_REQUEST);
            }

            if ($this->repo->findOneBy(['email' => $data['email']])) {
                return $this->error('Utilisateur deja existant avec cette adresse mail', [], $operation, Response::HTTP_CONFLICT);
            }

            $merchantConfig = $mcrepo->findOneBy(['shortcode' => strtolower($data['merchant'])]);
            if (!$merchantConfig) {
                return $this->error('Configuration de marchand non trouvee', [], $operation, Response::HTTP_BAD_REQUEST);
            }

            $user = (new User())
                ->setFirstname($data['firstname'])
                ->setLastname($data['lastname'])
                ->setEmail($data['email'])
                ->setUsername($usernameGenerator->generate($data['firstname'], $data['lastname']))
                ->setConfiguration($merchantConfig);
            $plainPassword = $passwordGenerator->generate();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $em->persist($user);
            $em->flush();

            $mailSent = true;
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
                            'operation' => $operation,
                        ])
                );
            } catch (\Throwable $e) {
                $mailSent = false;
                $logger->error('Echec de l\'envoi du mail de creation utilisateur.', [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->success([
                'username' => $user->getUsername(),
                'mailSent' => $mailSent,
            ], $operation, Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->error('Une erreur est survenue', [$e->getMessage()], $operation, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}