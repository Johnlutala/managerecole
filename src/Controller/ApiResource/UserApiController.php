<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Dto\Api\UserCreationPayload;
use App\Entity\User;
use App\Repository\MerchantConfigurationRepository;
use App\Repository\UserRepository;
use App\Service\UsernameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Routing\Attribute\Route;



#[Route('/api/rest/v1/users')]
final class UserApiController extends AbstractApiController
{

    private UserRepository $repo;

    public function __construct(
        UserRepository $repo_,
    )
    {
        $this->repo = $repo_;
    }


    #[Route('', name: 'api_get_enabled_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        $operation = "Récupération des utilisateurs";


        try {
            $users = $this->repo->findBy([
                'enabled' => true,
                'deleted' => false,
            ]);
            $data = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);
            $users = json_decode($data, true);

            //dd($users);

            return $this->success(
                $users,
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


    #[Route('', name: 'api_create_user', methods: ['POST'])]
    public function createUser(
        Request $request,
        MerchantConfigurationRepository $mcrepo,
        UsernameGenerator $usernameGenerator,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $operation = "Création d'utilisateur";

        try {
            $data = $this->getJsonData($request);

            $factory = new PasswordHasherFactory([
                'common' => ['algorithm' => 'bcrypt'],
            ]);
            $hasher = $factory->getPasswordHasher('common');

            $validationMessages = $this->validateObject(new UserCreationPayload(
                $data['firstname'] ?? '',
                $data['lastname'] ?? '',
                $data['email'] ?? '',
                $data['password'] ?? '',
                $data['merchant'] ?? ''
            ));

            if (count($validationMessages) > 0) {
                
                return $this->error(
                    "Données invalides",
                    $validationMessages,
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérification de l'existence
            $existing = $this->repo->findOneBy(['email' => $data['email']]);
            if ($existing) {
                
                return $this->error(
                    "Utilisateur déjà existant avec cette adresse mail",
                    [],
                    $operation,
                    Response::HTTP_CONFLICT
                );
            }


            // Vérifier l'existence de la configuration du marchand
            $merchantConfig = $mcrepo->findOneBy(['shortcode' => strtolower($data['merchant'])]);
            if (!$merchantConfig) {
                
                return $this->error(
                    "Configuration de marchand non trouvée",
                    [],
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = new User();
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setEmail($data['email']);
            $user->setUsername(
                $usernameGenerator->generate(
                    $data['firstname'],
                    $data['lastname']
                )
            );
            $user->setPassword($hasher->hash($data['password']));
            $user->setConfiguration($merchantConfig);

            $em->persist($user);
            $em->flush();

            return $this->success(
                [
                    'username' => $user->getUsername()
                ],
                $operation,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            
            return $this->error(
                'Une erreur est survenue',
                [$e->getTrace()],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
