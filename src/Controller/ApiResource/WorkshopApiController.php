<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\Workshop;
use App\Entity\WorkshopDay;
use App\Repository\UserRepository;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
use App\Service\Flexroll;
use App\Service\TokenEncoder;
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

#[Route('/api/rest/v1/workshops')]
final class WorkshopApiController extends AbstractApiController
{

    private WorkshopRepository  $repo;
    private EntityManagerInterface $entityManager;
    private TokenEncoder $tokenService;

    public function __construct(
        SerializerInterface $serializer,
        HttpClientInterface $httpClient,
        TransportInterface $mailer,
        LoggerInterface $logger,
        LoggerInterface $handshakeLogger,
        ValidatorInterface $validator,
        UserRepository $userRepo,
        WorkshopRepository $repo_,
        EntityManagerInterface $entityManager,
        TokenEncoder $tokenService
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator, $userRepo);
        $this->repo = $repo_;
        $this->entityManager = $entityManager;
        $this->tokenService = $tokenService;
    }

    
    #[Route('', name: 'api_create_workshop', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository  $userRepo
    ): JsonResponse
    {
        try {

            $authUser = $this->authenticateUser($request, $this->tokenService);

            if (!$authUser) {
                return new JsonResponse([
                    'code' => '3',
                    'message' => 'Authentification échouée: Token manquant ou invalide'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer les données JSON
            $data = json_decode($request->getContent(), true);
            
            // Validation simple
            $errors = $this->validateWorkshopData($data);
            if (!empty($errors)) {
                return new JsonResponse([
                    'code' => "1",
                    'message' => 'Validation échouée',
                    'errors' => $errors
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // PSEUDO UTILISATEUR pour demo
            $pseudoUser = $userRepo->findOneBy(['username' => 'rubuz.l']); 

            // Créer l'atelier
            $workshop = new Workshop();
            $workshop->setName($data['name']);
            $workshop->setDescription($data['description'] ?? null);
            $workshop->setDailyAmount($data['dailyAmount'] ?? 0);
            $workshop->setCurrency($data['currency'] ?? null);
            $workshop->setIsEnded(false);
            $workshop->setCreatedBy($authUser);
            $workshop->setConfiguration($authUser->getConfiguration());

            // Création des jours d'atelier
            foreach ($data['dates'] as $dateString) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateString);
                if ($date) {
                    $workshopDay = new WorkshopDay();
                    $workshopDay->setDate($date);
                    $workshopDay->setWorkshop($workshop);
                    $workshopDay->setIsClosed(false);
                    $workshopDay->setCreatedBy($authUser);
                    $this->entityManager->persist($workshopDay);
                }
            }

            $this->entityManager->persist($workshop);
            $this->entityManager->flush();

            return new JsonResponse([
                'code' => "0",
                'message' => 'Atelier créé avec succès'
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'code' => "1",
                'message' => 'Erreur lors de la création de l\'atelier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route("/", name:"api_get_workshops", methods: ['GET'])]
    public function getWorkshops(Request $request): JsonResponse
    {
        
        try {
           
            $token = $request->headers->get('x-api-token');
            $keyword = $request->query->get('keyword');

            if ($token) {

                $authUser = $this->authenticateUser($request, $this->tokenService);
                if (!$authUser) {
                    return new JsonResponse([
                        'code' => '3',
                        'message' => 'Authentification échouée: Token manquant ou invalide'
                    ], Response::HTTP_UNAUTHORIZED);
                } else {
                    $workshops = $this->repo->findActiveByUser($authUser, $keyword);
                }
            } else {
                
                $workshops = $this->repo->findAll([
                    'enabled' => true,
                    'isEnded' => false,
                    'deleted' => false,
                    'createdAt' => 'DESC'
                ]);
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
        
        try {

            $token = $request->headers->get('x-api-token');
            if ($token) {

                $authUser = $this->authenticateUser($request, $this->tokenService);
                if (!$authUser) {
                    return new JsonResponse([
                        'code' => '3',
                        'message' => 'Authentification échouée: Token manquant ou invalide'
                    ], Response::HTTP_UNAUTHORIZED);
                } else {
                    $workshops = $this->repo->findLatestByUser($authUser);
                }
            } else {
                
                $workshops = $this->repo->findAll([
                    'enabled' => true,
                    'isEnded' => false,
                    'deleted' => false,
                    'createdAt' => 'DESC'
                ], 3);
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
        try {

            $authUser = $this->authenticateUser($request, $this->tokenService);

            if (!$authUser) {
                return new JsonResponse([
                    'code' => '3',
                    'message' => 'Authentification échouée: Token manquant ou invalide'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true);
            
            $workshop = $this->repo->findOneBy(['id' => $data['workshopId']]);

            if (!$workshop) {
                
                return new JsonResponse([
                    'code' => "1",
                    'message' => 'Atelier non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($workshop->getCreatedBy() !== $authUser) {
                return new JsonResponse([
                    'code' => '3',
                    'message' => 'Permission refusée'
                ], Response::HTTP_FORBIDDEN);
            }

            // Générer la liste des participants
            $finalList = $workshopListGen->generateList($workshop);

            dd($finalList);

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
                    $this->entityManager->persist($participation);
                }

                $day->setIsClosed(true);
                $day->setUpdatedAt(new \DateTime());
                $this->entityManager->persist($day);
            }

            $workshop->setUpdatedAt(new \DateTime());
            $workshop->setIsEnded(true);
            $workshop->setEnabled(false);

            $this->entityManager->persist($workshop);
            $this->entityManager->flush();


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
                        'fullname' => $participation->getParticipant()->getDemographic()->getFullname(),
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

            $days = $workshopDayRepo->findActiveByWorkshop($workshop);
    
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

    private function validateWorkshopData(?array $data): array
    {
        $errors = [];

        if (empty($data)) {
            $errors[] = ['field' => 'payload', 'message' => 'Données JSON invalides ou manquantes'];
            return $errors;
        }

        // Validation du nom
        if (empty($data['name'])) {
            $errors[] = ['field' => 'name', 'message' => 'Le nom est obligatoire'];
        } elseif (strlen($data['name']) < 3) {
            $errors[] = ['field' => 'name', 'message' => 'Le nom doit contenir au moins 3 caractères'];
        } elseif (strlen($data['name']) > 100) {
            $errors[] = ['field' => 'name', 'message' => 'Le nom ne peut pas dépasser 100 caractères'];
        }

        /* // Validation du montant journalier
        if (!isset($data['dailyAmount'])) {
            $errors[] = ['field' => 'dailyAmount', 'message' => 'Le montant journalier est obligatoire'];
        } elseif (!is_numeric($data['dailyAmount']) || $data['dailyAmount'] <= 0) {
            $errors[] = ['field' => 'dailyAmount', 'message' => 'Le montant journalier doit être un nombre positif'];
        }

        // Validation de la devise
        if (empty($data['currency'])) {
            $errors[] = ['field' => 'currency', 'message' => 'La devise est obligatoire'];
        } elseif (strlen($data['currency']) < 3 || strlen($data['currency']) > 5) {
            $errors[] = ['field' => 'currency', 'message' => 'La devise doit contenir entre 3 et 5 caractères'];
        } */

        // Validation des dates
        if (!isset($data['dates'])) {
            $errors[] = ['field' => 'dates', 'message' => 'Les dates sont obligatoires'];
        } elseif (!is_array($data['dates'])) {
            $errors[] = ['field' => 'dates', 'message' => 'Les dates doivent être un tableau'];
        } elseif (empty($data['dates'])) {
            $errors[] = ['field' => 'dates', 'message' => 'Au moins une date est requise'];
        }

        // Validation de la description (optionnelle)
        if (isset($data['description']) && strlen($data['description']) > 500) {
            $errors[] = ['field' => 'description', 'message' => 'La description ne peut pas dépasser 500 caractères'];
        }

        return $errors;
    }
}
