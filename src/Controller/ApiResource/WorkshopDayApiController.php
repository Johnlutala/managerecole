<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Repository\WorkshopDayRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rest/v1/workshopdays')]
final class WorkshopDayApiController extends AbstractApiController
{

    private WorkshopDayRepository  $repo;

    public function __construct(
        WorkshopDayRepository $repo_,
    )
    {
        $this->repo = $repo_;
    }

    

    #[Route("/close", name:"api_close_one_day", methods: ['POST'])]
    public function closeOneDay(
        Request $request   
    ): JsonResponse
    {
        $operation = "Clôture d'un jour d'atelier";
        try {

            $checkTokenResult = $this->checkAuthentication($request);
        
            if (isset($checkTokenResult['status']) && $checkTokenResult['status'] === false) {
                
                return $this->error(
                    "Echec de cloture d'atelier",
                    [
                        "message" => $checkTokenResult["message"]
                    ],
                    $operation,
                    $checkTokenResult['code']
                );
            }


            // Récupérer les données JSON
            $data = $this->getJsonData($request);
            
            $day = $this->repo->findOneBy(['id' => $data['workshopDayId']]);


            if (!$day) {

                return new JsonResponse([
                    'code' => "1",
                    'message' => 'Jour d\'atelier non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            
            
            $day->setUpdatedAt(new \DateTime());
            $day->setIsClosed(true);
            $day->setEnabled(false);
            

            $this->em->persist($day);
            $this->em->flush();

            


            return new JsonResponse([
                'code' => "0",
                'message' => 'Atelier clôturé avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Error fetching participants: ' . $e->getMessage(), ['exception' => $e]);

            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la clôture du jour d\'atelier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }
    
}
