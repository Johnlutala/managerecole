<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\MerchantConfiguration;
use App\Repository\MerchantConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;



#[Route('/api/rest/v1/configurations')]
final class MerchantConfigurationApiController extends AbstractApiController
{

    private MerchantConfigurationRepository $repo;

    public function __construct(
        MerchantConfigurationRepository $repo_,
        // Dependencies from AbstractApiController
        SerializerInterface $serializer,
        HttpClientInterface $httpClient,
        TransportInterface $mailer,
        LoggerInterface $logger,
        LoggerInterface $handshakeLogger,
        ValidatorInterface $validator
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator);
        $this->repo = $repo_;
    }




    #[Route('', name: 'api_create_configuration', methods: ['POST'])]
    public function createConfiguration(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

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
            $required = ['shortcode'];
            foreach ($required as $field) {
                
                if (empty($data[$field])) {
                    
                    return new JsonResponse([
                        'code' => "1",
                        'message' => "Le champ '$field' est obligatoire"
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Vérification de l'existence
            $existing = $this->repo->findOneBy(['shortcode' => $data['shortcode']]);
            if ($existing) {
                
                return new JsonResponse([
                    'code' => "1",
                    'message' => "Une configuration avec ce shortcode existe déjà"
                ], Response::HTTP_CONFLICT);
            }


            $configuration = new MerchantConfiguration();
            $configuration->setShortcode(strtolower($data['shortcode']));

            if (isset($data['token'])) {
                $configuration->setToken($data['token']);
            }

            $em->persist($configuration);
            $em->flush();

            return new JsonResponse(
                [
                    'code'=> "0",
                    'message' => 'Configuration créée avec succès', 
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


    #[Route('/{shortcode}', name: 'api_get_single_configuration', methods: ['GET'])]
    public function getConfiguration(
        string $shortcode
    ): JsonResponse
    {
        try {
            
            $configuration = $this->repo->findOneBy(['shortcode' => strtolower($shortcode)]);
            if (!$configuration) {
                
                return new JsonResponse([
                    'status' => false
                ], Response::HTTP_OK);
            }

            $data = $this->serializer->serialize($configuration, 'json', ['groups' => 'configuration:read']);
            $configuration = json_decode($data, true);

            return new JsonResponse(
                [
                    'status'=> true 
                ],
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
}
