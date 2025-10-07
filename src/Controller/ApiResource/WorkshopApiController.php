<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Dto\CreateWorkshopDto;
use App\Entity\Workshop;
use App\Entity\WorkshopDay;
use App\Repository\BiometricRepository;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Integer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;



#[Route('/api/rest/v1/workshops')]
final class WorkshopApiController extends AbstractApiController
{

    private WorkshopRepository  $repo;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SerializerInterface $serializer,
        HttpClientInterface $httpClient,
        TransportInterface $mailer,
        LoggerInterface $logger,
        LoggerInterface $handshakeLogger,
        ValidatorInterface $validator,
        WorkshopRepository $repo_,
        EntityManagerInterface $entityManager
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator);
        $this->repo = $repo_;
        $this->entityManager = $entityManager;
    }

    
    #[Route('', name: 'api_create_workshop', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
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

            // Créer l'atelier
            $workshop = new Workshop();
            $workshop->setName($data['name']);
            $workshop->setDescription($data['description'] ?? null);
            $workshop->setDailyAmount($data['dailyAmount']);
            $workshop->setCurrency($data['currency']);

            // Création des jours d'atelier
            foreach ($data['dates'] as $dateString) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateString);
                if ($date) {
                    $workshopDay = new WorkshopDay();
                    $workshopDay->setDate($date);
                    $workshopDay->setWorkshop($workshop);
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

            return new JsonResponse($workshopArray, Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération de l\'atelier: ' . $e->getMessage()
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

        // Validation du montant journalier
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
        }

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
