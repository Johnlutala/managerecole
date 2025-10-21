<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\Workshop;
use App\Entity\WorkshopDay;
use App\Repository\BiometricRepository;
use App\Repository\UserRepository;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
use App\Service\Flexroll;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\WorkshopListGeneration;

#[Route('/api/rest/v1/workshopdays')]
final class WorkshopDayApiController extends AbstractApiController
{

    private WorkshopDayRepository  $repo;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SerializerInterface $serializer,
        HttpClientInterface $httpClient,
        TransportInterface $mailer,
        LoggerInterface $logger,
        LoggerInterface $handshakeLogger,
        ValidatorInterface $validator,
        WorkshopDayRepository $repo_,
        EntityManagerInterface $entityManager
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator);
        $this->repo = $repo_;
        $this->entityManager = $entityManager;
    }

    
    #[Route("/", name:"api_get_workshops", methods: ['GET'])]
    public function getWorkshops(Request $request): JsonResponse
    {
        
        try {
            
            $queries = $request->query->all();
            
    
            if (isset($queries['active']) && $queries['active'] === 'true') {
                
                $workshops = $this->repo->findActive();
                $datas = $this->serializer->serialize($workshops, 'json', ['groups' => 'workshop:read']);
                
                return new JsonResponse($datas, Response::HTTP_OK, [], true);
                
            }
            
            $workshops = $this->repo->findAll();

            $datas = $this->serializer->serialize($workshops, 'json', ['groups' => 'workshop:read']);
            
            return new JsonResponse($datas, Response::HTTP_OK, [], true);
    
            
        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération des ateliers: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    

    #[Route("/close", name:"api_close_one_day", methods: ['POST'])]
    public function closeOneDay(
        Request $request    
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
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
            

            $this->entityManager->persist($day);
            $this->entityManager->flush();

            


            return new JsonResponse([
                'code' => "0",
                'message' => 'Atelier clôturé avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération de l\'atelier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }
    
}
