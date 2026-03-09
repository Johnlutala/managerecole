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
        UserRepository $userRepo,
        WorkshopDayRepository $repo_,
        EntityManagerInterface $entityManager
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator, $userRepo);
        $this->repo = $repo_;
        $this->entityManager = $entityManager;
    }

    

    #[Route("/close", name:"api_close_one_day", methods: ['POST'])]
    public function closeOneDay(
        Request $request   
    ): JsonResponse
    {
        try {

            $authUser = $this->authenticateUser($request, $tokenService);

            if (!$authUser) {
                return new JsonResponse([
                    'code' => '3',
                    'message' => 'Authentification échouée: Token manquant ou invalide'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true);
            
            $day = $this->repo->findOneBy(['id' => $data['workshopDayId']]);

            // dd($day);

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
            $this->logger->error('Error fetching participants: ' . $e->getMessage(), ['exception' => $e]);

            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la clôture du jour d\'atelier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }
    
}
