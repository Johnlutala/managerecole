<?php

namespace App\Controller\ApiResource;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AuthApiController extends AbstractApiController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = $this->getJsonData($request);
        $username = trim((string) ($data['username'] ?? $data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->error(
                'Les champs username et password sont obligatoires.',
                [],
                'Connexion mobile',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $userRepository->findOneBy([
            'username' => $username,
            'enabled' => true,
            'deleted' => false,
        ]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->error(
                'Identifiants invalides.',
                [],
                'Connexion mobile',
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $this->success([
            'token' => $this->jwt->generateToken([
                'shortcode' => $user->getCode() ?? (string) $user->getId(),
                'username' => $user->getUserIdentifier(),
            ]),
            'expiresIn' => 1800,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUserIdentifier(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'roles' => $user->getRoles(),
            ],
        ], 'Connexion mobile');
    }
}
