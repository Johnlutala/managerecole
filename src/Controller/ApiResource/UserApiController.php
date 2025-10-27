<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\User;
use App\Repository\MerchantConfigurationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;



#[Route('/api/rest/v1/users')]
final class UserApiController extends AbstractApiController
{

    private UserRepository $repo;

    public function __construct(
        UserRepository $repo_,
        // Dependencies from AbstractApiController
        SerializerInterface $serializer,
        HttpClientInterface $httpClient,
        TransportInterface $mailer,
        LoggerInterface $logger,
        LoggerInterface $handshakeLogger,
        ValidatorInterface $validator
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator, $repo_);
        $this->repo = $repo_;
    }


    #[Route('', name: 'api_get_enabled_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        try {
            $users = $this->repo->findEnabled();
            $data = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);
            $users = json_decode($data, true);

            //dd($users);

            return new JsonResponse(
                $users,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            
            return new JsonResponse(
                [
                    'code'=> "2",
                    'message' => 'Une erreur est survenue ' . $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    #[Route('', name: 'api_create_user', methods: ['POST'])]
    public function createUser(
        Request $request,
        MerchantConfigurationRepository $mcrepo,
        EntityManagerInterface $em
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $factory = new PasswordHasherFactory([
                'common' => ['algorithm' => 'bcrypt'],
            ]);
            $hasher = $factory->getPasswordHasher('common');

            if (!$data) {
                
                return new JsonResponse(
                    [
                        'code'=> "1",
                        'message' => 'Données manquantes ou incorrectes'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérification des champs requis
            $required = ['firstname', 'lastname', 'email', 'username', 'password', 'merchant'];
            foreach ($required as $field) {
                
                if (empty($data[$field])) {
                    
                    return new JsonResponse([
                        'code' => "1",
                        'message' => "Le champ '$field' est obligatoire"
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Vérification de l'existence
            $existing = $this->repo->findOneBy(['email' => $data['email']]);
            if ($existing) {
                
                return new JsonResponse([
                    'code' => "1",
                    'message' => "Un utilisateur avec cet e-mail existe déjà"
                ], Response::HTTP_CONFLICT);
            }


            // Vérifier l'existence de la configuration du marchand
            $merchantConfig = $mcrepo->findOneBy(['shortcode' => strtolower($data['merchant'])]);
            if (!$merchantConfig) {
                
                return new JsonResponse([
                    'code' => "1",
                    'message' => "Configuration du marchand introuvable"
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = new User();
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setPassword($hasher->hash($data['password']));
            $user->setConfiguration($merchantConfig);

            $em->persist($user);
            $em->flush();

            return new JsonResponse(
                [
                    'code'=> "0",
                    'message' => 'Utilisateur ' . $data['username'] . ' créé avec succès',
                ],
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            
            return new JsonResponse(
                [
                    'code'=> "2",
                    'message' => 'Une erreur est survenue ' . $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
