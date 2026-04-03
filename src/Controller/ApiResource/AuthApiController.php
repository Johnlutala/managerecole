<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Dto\Api\LoginPayload;
use App\Repository\UserRepository;
use App\Service\JwtTokenService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/auth')]
final class AuthApiController extends AbstractApiController
{
    private UserRepository $repo;

    public function __construct(
        UserRepository $repo_
    )
    {
        $this->repo = $repo_;
        
    }

    

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        JwtTokenService $jwt,
    ): JsonResponse
    {

        try {
                
            $operation = "Authentification";

            $data = $this->getJsonData($request);
            $payload = new LoginPayload(
                $data['username'],
                $data['password']
            );

            $validationMessages = $this->validateObject($payload);

            if (count($validationMessages) > 0) {
                
                return $this->error(
                    "Données invalides",
                    $validationMessages,
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = $this->repo->findOneBy([
                'username' => $data['username'],
            ]);

            if (!$user) {
                return $this->error(
                    'Utilisateur invalide',
                    [],
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }

            $isPasswordValid = $user->verifyPassword($data['password']);

            if ($isPasswordValid === false) {
                
                return $this->error(
                    'Mot de passe invalide',
                    [],
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérifier que l'utilisateur a une configuration
            $configuration = $user->getConfiguration();
            if (!$configuration) {
                return $this->error(
                    "Utilisateur invalide",
                    ['merchant' => $configuration],
                    "Authentification",
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Generer le token JWT
            $token = $jwt->generateToken([
                'username' => $user->getUsername(),
                'shortcode' => $configuration->getShortcode()
            ]);

            return $this->success(
                [
                    'token' => $token                
                ],
                $operation,
                Response::HTTP_OK
            );


        } catch (\Throwable $e) {
            return $this->error(
                "Une erreur est survenue lors de l'authentification",
                ['exception' => $e->getMessage()],
                "Authentification",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }

}
