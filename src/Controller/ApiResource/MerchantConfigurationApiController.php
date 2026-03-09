<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Dto\Api\MerchantConfigPayload;
use App\Entity\MerchantConfiguration;
use App\Repository\MerchantConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



#[Route('/api/rest/v1/configurations')]
final class MerchantConfigurationApiController extends AbstractApiController
{

    private MerchantConfigurationRepository $repo;

    public function __construct(
        MerchantConfigurationRepository $repo_,
    )
    {
        $this->repo = $repo_;
    }




    #[Route('', name: 'api_create_configuration', methods: ['POST'])]
    public function createConfiguration(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $operation = "Création de configuration marchand";

        try {
            $data = $this->getJsonData($request);

            $validationMessages = $this->validateObject(new MerchantConfigPayload(
                $data['shortcode'] ?? '',
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
            $existing = $this->repo->findOneBy(['shortcode' => $data['shortcode']]);
            if ($existing) {
                
                return $this->error(
                    "Configuration déjà existante pour ce code marchand",
                    [],
                    $operation,
                    Response::HTTP_CONFLICT
                );
            }


            $configuration = new MerchantConfiguration();
            $configuration->setShortcode(strtolower($data['shortcode']));


            $em->persist($configuration);
            $em->flush();

            return $this->success(
                null,
                $operation,
                Response::HTTP_CREATED
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
