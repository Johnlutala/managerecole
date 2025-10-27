<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\User;
use App\Repository\MerchantConfigurationRepository;
use App\Repository\UserRepository;
use App\Service\TokenEncoder;
use App\Service\TokenGeneration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/rest/v1/auth')]
final class AuthApiController extends AbstractApiController
{
    private UserRepository $repo;

    public function __construct(
        UserRepository $repo_,
        SerializerInterface $serializer,
        HttpClientInterface $httpClient,
        TransportInterface $mailer,
        LoggerInterface $logger,
        LoggerInterface $handshakeLogger,
        ValidatorInterface $validator
    ) {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator);
        $this->repo = $repo_;
    }

    #[Route('', name: 'api_create_user', methods: ['POST'])]
    public function createUser(
        Request $request,
        MerchantConfigurationRepository $mcrepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['code'=> "1", 'message' => 'Données manquantes ou incorrectes'], 400);
            }

            // Vérification des champs requis
            $required = ['firstname', 'lastname', 'email', 'username', 'password', 'merchant'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->json(['code'=> "1", 'message' => "Le champ '$field' est obligatoire"], 400);
                }
            }

            // Vérification de l'existence d'un utilisateur
            if ($this->repo->findOneBy(['email' => $data['email']])) {
                return $this->json(['code'=> "1", 'message' => "Un utilisateur avec cet e-mail existe déjà"], 409);
            }

            // Vérification du marchand
            $merchantConfig = $mcrepo->findOneBy(['shortcode' => strtolower($data['merchant'])]);
            if (!$merchantConfig) {
                return $this->json(['code'=> "1", 'message' => "Configuration du marchand introuvable"], 400);
            }

            // Création de l'utilisateur
            $user = new User();
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setConfiguration($merchantConfig);

            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            return $this->json([
                'code'=> "0",
                'message' => 'Utilisateur ' . $data['username'] . ' créé avec succès',
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'code'=> "2",
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        TokenEncoder $tokenGeneration,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        $user = $this->repo->findOneBy([
            'username' => $data['username'],
        ]);

        if (!$user) {
            return new JsonResponse([
                'message' => 'Identifiant ou mot de passe incorrect'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'message' => 'Identifiant ou mot de passe incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que l'utilisateur a une configuration
        $configuration = $user->getConfiguration();
        if (!$configuration) {
            return $this->json([
                'message' => 'Configuration utilisateur manquante'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Generer le token JWT
        $token = $tokenGeneration->generateToken("+1 minute", $user->getUsername());

        try {
            
            return new JsonResponse(
                [
                    'code'=> "0",
                    'message' => 'Authentification réussie',
                    'token' => $token
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la connexion: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
