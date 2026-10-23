<?php

namespace App\Controller\ApiResource;

use App\Entity\User;
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
        $identifier = trim((string)(
            $data['matricule'] ?? $data['username'] ?? $data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($identifier === '' || $password === '') return $this->error(
            'Le matricule (ou identifiant) et le mot de passe sont obligatoires.',
            [],
            'Connexion mobile',
            Response::HTTP_BAD_REQUEST
        );

        $user = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.eleve', 'e')
            ->leftJoin('u.professeur', 'p')
            ->andWhere('u.enabled = :enabled')
            ->andWhere('u.deleted = :deleted')
            ->andWhere('u.username = :identifier OR u.email = :identifier OR e.matricule = :identifier OR p.matricule = :identifier')->setParameter('enabled', true)->setParameter('deleted', false)->setParameter('identifier', $identifier)->getQuery()->getOneOrNullResult();

        if (!$user || !$passwordHasher->isPasswordValid($user, $password))
            return $this->error(
                'Identifiants invalides.',
                [],
                'Connexion mobile',
                Response::HTTP_UNAUTHORIZED
            );
        return $this->success(['token' => $this->jwt->generateToken([
            'shortcode' => $user->getCode() ?? (string)$user->getId(),
            'username' => $user->getUserIdentifier()
        ]), 'expiresIn' => 1800, 'user' => [
            'id' => $user->getId(),
            'username' => $user->getUserIdentifier(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'roles' => $user->getRoles(),
            'espace' => $this->space($user)
        ]], 'Connexion mobile');
    }
    
    private function space(User $user): string
    {
        if ($user->getEleve()) return 'eleve';
        if ($user->getParent()) return 'parent';
        if ($user->getProfesseur()) return 'professeur';
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $user->getRoles())) return 'admin';
        return 'personnel';
    }
}
