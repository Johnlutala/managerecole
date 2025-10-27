<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\Biometric;
use App\Entity\Company;
use App\Entity\Demographic;
use App\Entity\Participant;
use App\Entity\Participation;
use App\Repository\BiometricRepository;
use App\Repository\DemographicRepository;
use App\Repository\ParticipantRepository;
use App\Repository\ParticipationRepository;
use App\Repository\UserRepository;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
use App\Service\TokenEncoder;
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



#[Route('/api/rest/v1/participants')]
final class ParticipantApiController extends AbstractApiController
{

    private ParticipantRepository $repo;
    private DemographicRepository $demoRepo;
    private BiometricRepository $bioRepo;

    public function __construct(
        ParticipantRepository $repo_,
        DemographicRepository $demoRepo_,
        BiometricRepository $bioRepo_,
        SerializerInterface $serializer,
        HttpClientInterface $httpClient,
        TransportInterface $mailer,
        LoggerInterface $logger,
        LoggerInterface $handshakeLogger,
        ValidatorInterface $validator,
        UserRepository $userRepo
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator, $userRepo);
        $this->repo = $repo_;
        $this->demoRepo = $demoRepo_;
        $this->bioRepo = $bioRepo_;
    }

    public function getDemographics(
        Request $request
    ): void
    {
        # code...
    }

    #[Route('/with-biometrics', name: 'api_create_participant_with_bio', methods: ['POST'])]
    public function createWithBiometrics(
        Request $request,
        EntityManagerInterface $entityManager,
        WorkshopRepository $workshopRepo,
        TokenEncoder $tokenService
    ): JsonResponse {
        try {

            $authUser = $this->authenticateUser($request, $tokenService);

            if (!$authUser) {
                return new JsonResponse([
                    'code' => '3',
                    'message' => 'Authentification échouée: Token manquant ou invalide'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupération et décodage des données JSON
            $data = json_decode($request->getContent(), true);
    
            if (!$data) {
                return new JsonResponse([
                    'code' => '1',
                    'message' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }
    
            // Vérification des champs requis
            $required = ['firstname', 'lastname', 'gender', 'phoneContact'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse(['error' => "Le champ '$field' est obligatoire"], Response::HTTP_BAD_REQUEST);
                }
            }
    
            // Création de l'entité Demographic (informations personnelles)
            $demographic = new Demographic();
            $demographic->setFirstname($data['firstname']);
            $demographic->setMiddlename($data['middlename'] ?? null);
            $demographic->setLastname($data['lastname']);
            $demographic->setGender($data['gender']);
            $demographic->setPhone($data['phoneContact']);
            $demographic->setCreatedBy($authUser);
    
            // Création de l'entité Biometric (empreintes et visage)
            $biometric = new Biometric();
    
            if (!empty($data['biometrics']) && is_array($data['biometrics'])) {
                foreach ($data['biometrics'] as $bio) {
                    if (!isset($bio['pos']) || !isset($bio['data'])) continue;
    
                    // correspondance pos → champ de Biometric
                    switch ($bio['pos']) {
                        case 0: $biometric->setFaceData($bio['data']); break;
                        case 1: $biometric->setRightThumb($bio['data']); break;
                        case 2: $biometric->setRightIndex($bio['data']); break;
                        case 3: $biometric->setRightMiddle($bio['data']); break;
                        case 4: $biometric->setRightRing($bio['data']); break;
                        case 5: $biometric->setRightLittle($bio['data']); break;
                        case 6: $biometric->setLeftThumb($bio['data']); break;
                        case 7: $biometric->setLeftIndex($bio['data']); break;
                        case 8: $biometric->setLeftMiddle($bio['data']); break;
                        case 9: $biometric->setLeftRing($bio['data']); break;
                        case 10: $biometric->setLeftLittle($bio['data']); break;
                    }
                }
            }
    
            // Liaison Biometric ↔ Demographic
            $demographic->setBiometrics($biometric);
            $biometric->setDemographic($demographic);
    
            // Création du Participant
            $participant = new Participant();
            $participant->setPhone($data['phoneEMoney'] ?? $data['phoneContact']);
            $participant->setDemographic($demographic);
            $participant->setCreatedBy($authUser);
    
            // Récupération de la société (Company)
            if (isset($data['companyId'])) {
                $company = $entityManager->getRepository(Company::class)->find($data['companyId']);
                if ($company) {
                    $participant->setCompany($company);
                }
            }


            // Création des participations aux ateliers (Workshop)
            if (isset($data['workshopId'])) {
                
                $workshop = $workshopRepo->findOneBy(['id' => $data['workshopId']]);

                if (!$workshop) {
                    return new JsonResponse([
                        'code' => "1",
                        'message' => "Atelier  non trouvé pour l'ID fourni"
                    ], Response::HTTP_BAD_REQUEST);
                }
                
                foreach ($workshop->getWorkshopDays() as $day) {
                    
                    $participation = new Participation();
                    $participation->setParticipant($participant);
                    $participation->setWorkshop($workshop);
                    $participation->setDay($day);
                    $participation->setCreatedBy($authUser);
                    $entityManager->persist($participation);
                }
            }
            
    
            // Ajout à la collection de Demographic
            $demographic->addParticipant($participant);
    
            // Persistance en base
            $entityManager->persist($biometric);
            $entityManager->persist($demographic);
            $entityManager->persist($participant);
            $entityManager->flush();
    
            // Réponse
            return new JsonResponse([
                'code' => "0",
                'message' => 'Participant créé avec succès',
                // 'participant_id' => $participant->getId(),
                // 'demographic_id' => $demographic->getId(),
                // 'biometric_id' => $biometric->getId()
            ], Response::HTTP_CREATED);
    
        } catch (\Exception $e) {
            return new JsonResponse([
                'code' => "1",
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    

    #[Route('/link-to-demographics', name: 'api_link_participant_to_demographics', methods: ['POST'])]
    public function linkToDemographics(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {

        // Validation



        // Création de l'entité Participant

        return new JsonResponse([
            'message' => 'Participant créé avec succès'
        ], Response::HTTP_OK);
    }
    

    #[Route('/set-attendance', name: 'api_set_participant_attendance', methods: ['POST'])]
    public function setAttendance(
        Request $request,
        WorkshopDayRepository $workshopDayRepo,
        ParticipationRepository $participationRepo,
        EntityManagerInterface $entityManager,
        TokenEncoder $tokenService
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

            if (!$data) {
                return new JsonResponse([
                    'code' => "1",
                    'message' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }
        
            // Vérification des champs requis
            $required = ['participantId', 'workshopDayId', 'biometrics'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse([
                        'code' => "1",
                        'message' => "Le champ '$field' est obligatoire"
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $participant = $this->repo->findOneBy(['id' => $data['participantId']]);
            $workshopDay = $workshopDayRepo->findOneBy(['id' => $data['workshopDayId']]);

            

            if (!$participant || !$workshopDay) {
                return new JsonResponse([
                    'code' => "1",
                    'message' => "Participant ou jour d'atelier non trouvé pour les ID fournis"
                ], Response::HTTP_BAD_REQUEST);
            }

            $participation = $participationRepo->findOneByParticipantAndDay($workshopDay, $participant);

            //dd($participation);

            if (!$participation) {
                return new JsonResponse([
                    'code' => "1",
                    'message' => "Aucune participation trouvée pour le participant et le jour d'atelier spécifiés"
                ], Response::HTTP_BAD_REQUEST);
            }
    
            $participation->setIsPresent(true);
            $participation->setUpdatedAt(new \DateTime());

            $entityManager->persist($participation);
            $entityManager->flush();
    
            // Création de l'entité Participant
    
            return new JsonResponse([
                'code' => "0",
                'message' => 'Présence mise à jour avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }


    #[Route('/attendance/{workshopDayId}', name: 'api_get_attendance_per_day', methods: ['GET'])]
    public function getParticipants(
        int $workshopDayId,
        Request $request,
        WorkshopDayRepository $workshopDayRepo,
        ParticipationRepository $participationRepo
    ): JsonResponse
    {
        try {
            
            // Checks if the workshopDay exists
            $workshopDay = $workshopDayRepo->findOneBy(['id' => intval($workshopDayId)]);


            if (!$workshopDay) {
                return new JsonResponse([
                    'code' => "1",
                    'message' => "Jour d'atelier non trouvé pour l'ID fourni"
                ], Response::HTTP_BAD_REQUEST);
            }

            $attendances = $participationRepo->findActivePerWorkshopDay($workshopDay);
            $participantsArray = [];

            foreach ($attendances as $attendance) {
                $participant = $attendance->getParticipant();
                $demographic = $participant->getDemographic();

                $participantsArray[] = [
                    'id' => $participant->getId(),
                    'phone' => $participant->getPhone(),
                    'fullname' => $demographic ? trim($demographic->getFirstname() . ' ' . $demographic->getLastname() . ' ' . $demographic->getMiddlename()) : null,
                    'isPresent' => $attendance->isPresent(),
                    'attendanceId' => $attendance->getId(),
                ];
            }
            

            return new JsonResponse($participantsArray, Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Une erreur est survenue ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
