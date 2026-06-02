<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\Workshop;
use App\Entity\WorkshopDay;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
use App\Service\Flexroll;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\WorkshopListGeneration;
use App\Dto\Api\WorkshopPayload;

#[Route('/api/rest/v1/workshops')]
final class WorkshopApiController extends AbstractApiController
{

    private WorkshopRepository  $repo;
    private EntityManagerInterface $entityManager;

    public function __construct(
        WorkshopRepository $repo_
    )
    {
        $this->repo = $repo_;
    }

    
    #[Route('', name: 'api_create_workshop', methods: ['POST'])]
    public function create(
        Request $request
    ): JsonResponse
    {
        $operation = "Création d'atelier";
        try {

            // Authentification
            $checkTokenResult = $this->checkAuthentication($request);
        
            if (isset($checkTokenResult['status']) && $checkTokenResult['status'] === false) {
                
                return $this->error(
                    "Echec de la création d'atelier",
                    [
                        "message" => $checkTokenResult["message"]
                    ],
                    $operation,
                    $checkTokenResult['code']
                );
            }


            // Récupérer les données JSON
            $data = $this->getJsonData($request);
            
            // Validation simple
            $requestObject = new  WorkshopPayload(
                $data['name'],
                $data['description'],
                $data['dailyAmount'],
                $data['currency'],
                $data['dates']
            );

            $validationMessages = $this->validateObject($requestObject);

            //dd($requestObject->getPayload());

            if (count($validationMessages) > 0) {
                
                return $this->error(
                    "Données invalides",
                    $validationMessages,
                    $operation,
                    400
                );
            }
            

            // Créer l'atelier
            $workshop = new Workshop();
            $workshop->setName($data['name']);
            $workshop->setDescription($data['description'] ?? null);
            $workshop->setDailyAmount($data['dailyAmount'] ?? 0);
            $workshop->setCurrency($data['currency'] ?? null);
            $workshop->setIsEnded(false);
            $workshop->setCreatedBy($checkTokenResult['user']);
            $workshop->setConfiguration($checkTokenResult['user']->getConfiguration());

            // Création des jours d'atelier
            foreach ($data['dates'] as $dateString) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateString);
                if ($date) {
                    $workshopDay = new WorkshopDay();
                    $workshopDay->setDate($date);
                    $workshopDay->setWorkshop($workshop);
                    $workshopDay->setIsClosed(false);
                    $workshopDay->setCreatedBy($checkTokenResult['user']);
                    $this->em->persist($workshopDay);
                }
            }

            $this->em->persist($workshop);
            $this->em->flush();

            return $this->success(
                [
                    'id' => $workshop->getId(),
                    'name' => $workshop->getName(),
                    'description' => $workshop->getDescription(),
                ],
                $operation,
                Response::HTTP_CREATED
            );

        } catch (\Throwable $e) {
            return $this->error(
                'Erreur lors de la création de l\'atelier',
                ['message' => $e->getMessage()],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    #[Route("/", name:"api_get_workshops", methods: ['GET'])]
    public function getWorkshops(Request $request): JsonResponse
    {
        $operation = "Récupération de la liste des ateliers";
        $keyword = $request->query->get('keyword');
        try {
           
            $checkTokenResult = $this->checkAuthentication($request, false);
            //dd($checkTokenResult);
            if ($checkTokenResult['status'] === false) {

                return $this->error(
                    "Echec de la récupération des ateliers",
                    [
                        "message" => $checkTokenResult["message"]
                    ],
                    $operation,
                    $checkTokenResult['code']
                );
            } else {

                switch ($checkTokenResult['user']) {
                    case null:
                        $workshops = $this->repo->findBy([
                            'enabled' => true,
                            'isEnded' => false,
                            'deleted' => false
                        ], ['createdAt' => 'DESC']);
                        break;
                    
                    default:
                        $workshops = $this->repo->findActiveByUser($checkTokenResult['user'], $keyword);
                        break;
                } 
            }
               

            $datas = $this->serializer->serialize($workshops, 'json', ['groups' => 'workshop:read']);
            
            return new JsonResponse($datas, Response::HTTP_OK, [], true);
    
            
        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération des ateliers: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route("/latest", name:"api_get_latest_workshops", methods: ['GET'])]
    public function getLatestWorkshops(Request $request): JsonResponse
    {
        $operation = "Récupération de la liste des ateliers";
        try {

            $checkTokenResult = $this->checkAuthentication($request, false);
            //dd($checkTokenResult);
            if ($checkTokenResult['status'] === false) {

                return $this->error(
                    "Echec de la récupération des ateliers",
                    [
                        "message" => $checkTokenResult["message"]
                    ],
                    $operation,
                    $checkTokenResult['code']
                );
            } else {

                switch ($checkTokenResult['user']) {
                    case null:
                        $workshops = $this->repo->findBy([
                            'enabled' => true,
                            'isEnded' => false,
                            'deleted' => false
                        ], ['createdAt' => 'DESC'], 3);
                        break;
                    
                    default:
                        $workshops = $this->repo->findLatestByUser($checkTokenResult['user']);
                        break;
                } 
            }




            
            // Préparer les données des ateliers avec startDate et endDate
            $workshopsData = [];

            if (empty($workshops)) {
                return new JsonResponse([
                    'code' => '1',
                    'message' => 'Aucun atelier trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            foreach ($workshops as $workshop) {
                $startDate = null;
                $endDate = null;
                $dates = $workshop->getWorkshopDays()->map(fn($day) => $day->getDate())->toArray();
                
                foreach ($dates as $date) {
                    if ($date) {
                        if ($startDate === null || $date < $startDate) {
                            $startDate = $date;
                        }
                        if ($endDate === null || $date > $endDate) {
                            $endDate = $date;
                        }
                    }
                }
                

                $workshopsData[] = [
                    'id' => $workshop->getId(),
                    'name' => $workshop->getName(),
                    'description' => $workshop->getDescription(),
                    'startDate' => $startDate ? $startDate->format('d/m/Y') : null,
                    'endDate' => $endDate ? $endDate->format('d/m/Y') : null
                ];
            }

            return new JsonResponse($workshopsData, Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération des ateliers: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    #[Route("/close", name:"api_close_one_workshop", methods: ['POST'])]
    public function closeOneWorkshop(
        Request $request,
        WorkshopListGeneration $workshopListGen,
        Flexroll $flexrollService    
    ): JsonResponse
    {
        $operation = "Clôture d'atelier";
        try {

            $checkTokenResult = $this->checkAuthentication($request);
        
            if (isset($checkTokenResult['status']) && $checkTokenResult['status'] === false) {
                
                return $this->error(
                    "Echec de la création d'atelier",
                    [
                        "message" => $checkTokenResult["message"]
                    ],
                    $operation,
                    $checkTokenResult['code']
                );
            }


            // Récupérer les données JSON
            $data = $this->getJsonData($request);
            
            $workshop = $this->repo->findOneBy(['id' => $data['workshopId']]);

            if (!$workshop) {
                
                return new JsonResponse([
                    'code' => "1",
                    'message' => 'Atelier non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($workshop->getCreatedBy() !== $checkTokenResult['user']) {
                return new JsonResponse([
                    'code' => '3',
                    'message' => 'Permission refusée'
                ], Response::HTTP_FORBIDDEN);
            }

            // Générer la liste des participants
            $finalList = $workshopListGen->generateList($workshop);

            $this->logger->info('finalList generated: ' . json_encode($finalList, true));
            //dd($finalList);
            

            // Envoyer la liste à Flexroll
            $response = $flexrollService->sendList($finalList);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                return new JsonResponse([
                    'code' => "3",
                    'message' => 'Erreur lors de l\'envoi de la liste à Flexroll'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }


        
            // Désactiver les participations et les jours associés
            foreach ($workshop->getWorkshopDays() as $day) {

                foreach ($day->getParticipations() as $participation) {
                    $participation->setEnabled(false);
                    $participation->setUpdatedAt(new \DateTime());
                    $this->em->persist($participation);
                }

                $day->setIsClosed(true);
                $day->setUpdatedAt(new \DateTime());
                $this->em->persist($day);
            }

            $workshop->setUpdatedAt(new \DateTime());
            $workshop->setIsEnded(true);
            $workshop->setEnabled(false);

            $this->em->persist($workshop);
            $this->em->flush();


            return new JsonResponse([
                'code' => "0",
                'data'=>$finalList,
                'message' => 'Atelier clôturé avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération de l\'atelier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }
    

    #[Route("/{id}", name:"api_get_one_workshop", methods: ['GET'])]
    public function getOneWorkshop(
        string $id,
        WorkshopDayRepository $workshopDayRepo    
    ): JsonResponse
    {
        
        try {
            $workshop = $this->repo->findOneBy(['id' => intval($id)]);
            
            $days = $workshopDayRepo->findActiveByWorkshop($id);
            $startDate = null;
            $endDate = null;

            foreach ($days as $day) {
                $date = $day->getDate();
                if ($date) {
                    if ($startDate === null || $date < $startDate) {
                        $startDate = $date;
                    }
                    if ($endDate === null || $date > $endDate || $date === $endDate) {
                        $endDate = $date;
                    }
                }
            }

            if (!$workshop) {
                
                return new JsonResponse([
                    'code' => "1",
                    'message' => 'Atelier non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
    
            $workshopData = $this->serializer->serialize($workshop, 'json', ['groups' => 'workshop:read']);
            $workshopArray = json_decode($workshopData, true);


            $workshopArray['startDate'] = $startDate ? $startDate->format('d/m/Y') : null;
            $workshopArray['endDate'] = $endDate ? $endDate->format('d/m/Y') : null;
            $workshopArray['numberOfDays'] = count($days);

            $workshopArray['participants'] = [];

            foreach ($workshop->getWorkshopDays() as $day) {
                foreach ($day->getParticipations() as $participation) {

                    $participantId = $participation->getParticipant()->getId();
                    $participant = [
                        'id' => $participantId,
                        'fullname' => $participation->getParticipant()->getFullname(),
                        'phone' => $participation->getParticipant()->getPhone()
                    ];
                    if (!in_array($participantId, array_column($workshopArray['participants'], 'id'))) {
                        $workshopArray['participants'][] = $participant;
                    }
                }
            }

            return new JsonResponse($workshopArray, Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération de l\'atelier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }
    

    #[Route("/{id}/days", name:"api_get_one_workshop_days", methods: ['GET'])]
    public function getWorkshopDays(
        string $id,
        WorkshopDayRepository $workshopDayRepo    
    ): JsonResponse
    {
        
        try {
            $workshop = $this->repo->findOneBy(['id' => intval($id)]);
            
            if (!$workshop) {
                
                return new JsonResponse([
                    'code' => "1",
                    'message' => 'Atelier non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $days = $workshopDayRepo->findByWorkshop($workshop);
    
            $data = $this->serializer->serialize($days, 'json', ['groups' => 'workshopday:read']);
            $dataArray = json_decode($data, true);
                foreach ($dataArray as &$day) {
                    if (isset($day['date'])) {
                        $date = new \DateTime($day['date']);
                        $day['date'] = $date->format('d/m/Y');
                    }
                }

            return new JsonResponse($dataArray, Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération de l\'atelier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }


    #[Route("/{id}/stats", name:"api_get_one_workshop_stats", methods: ['GET'])]
    public function getWorkshopStats(int $id): JsonResponse
    {
        try {
            
            $workshop = $this->repo->findOneBy(['id' => intval($id)]);
            
            // Vérification de l'existence de l'atelier
            if (!$workshop) {
                
                return new JsonResponse([
                    'code' => "1",
                    'message' => 'Atelier non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $expectedParticipations = 0;
            $attendances = [];
            $totalDays = $workshop->getWorkshopDays()->count();

            foreach ($workshop->getWorkshopDays() as $day) {
                $expectedParticipations += $day->getParticipations()->count();
                $trueAttendances = 0;
                
                foreach ($day->getParticipations() as $participation) {
                    if ($participation->isPresent()) {
                        $trueAttendances++;
                    }
                }
                $attendances[] = [
                    'date' => $day->getDate()->format('d/m/Y'),
                    'participants' => $trueAttendances
                ];
            }

            $attendanceRate = $expectedParticipations > 0 ? array_sum(array_column($attendances, 'participants')) * 100 / $expectedParticipations : 0;

            // Parcourir les présences et ressortir les participants. Si un participant est présent plusieurs fois, ne compter qu'une seule fois.
            $uniqueParticipants = [];
            foreach ($workshop->getWorkshopDays() as $day) {
                foreach ($day->getParticipations() as $participation) {
                    if ($participation->isPresent()) {
                        $participantId = $participation->getParticipant()->getId();
                        if (!in_array($participantId, $uniqueParticipants)) {
                            $uniqueParticipants[] = $participantId;
                        }
                    }
                }
            }

            $stats = [
                'attendanceRate' => round($attendanceRate, 2),
                'totalParticipants' => count($uniqueParticipants),
                'days' => [
                    'total' => $totalDays,
                    'attendances' => $attendances
                ]
            ];

            return new JsonResponse($stats, Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération des statistiques de l\'atelier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
}
