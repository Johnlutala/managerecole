<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Dto\Api\ParticipantCreationPayload;
use App\Dto\Api\ParticipantLinkPayload;
use App\Entity\Biometric;
use App\Entity\Participant;
use App\Entity\Participation;
use App\Repository\ParticipantRepository;
use App\Repository\ParticipationRepository;
use App\Repository\UserRepository;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api/rest/v1/participants')]
final class ParticipantApiController extends AbstractApiController
{

    private ParticipantRepository $repo;
    private WorkshopRepository $workshopRepo;
    private UserRepository $userRepo;

    public function __construct(
        ParticipantRepository $repo_,
        WorkshopRepository $workshopRepo_,
        UserRepository $userRepo_
    )
    {
        $this->repo = $repo_;
        $this->workshopRepo = $workshopRepo_;
        $this->userRepo = $userRepo_;
    }

    #[Route('', name: 'api_get_all_participants', methods: ['GET'])]
    public function getAll(
        Request $request
    ): Response
    {
        try {
            $keyword = $request->query->get('keyword');
            $workShopId = $request->query->get('workshopId');

            $workshop = $this->workshopRepo->findOneBy(['id' => (int) $workShopId]);

            $data = [];
            if (!$workshop) {
                $participants = $this->repo->findActive($keyword);

                foreach ($participants as $participant) {
                    $data[] = [
                        'id' => $participant->getId(),
                        'firstname' => $participant->getFirstname(),
                        'lastname' => $participant->getLastname(),
                        'mobileMoney' => $participant->getPhoneMobileMoney()
                    ];
                }
            }

            $participants = $this->repo->findActiveWithoutParticipation($keyword, $workshop);

            foreach ($participants as $participant) {
                $data[] = [
                        'id' => $participant->getId(),
                        'firstname' => $participant->getFirstname(),
                        'lastname' => $participant->getLastname(),
                        'mobileMoney' => $participant->getPhoneMobileMoney()
                    ];
            }


            return $this->success(
                $data,
                "Récupération des participants",
                Response::HTTP_OK
            );

        } catch (\Throwable $e) {
            return $this->error(
                "Une erreur est survenue",
                [$e->getMessage()],
                "Récupération des participants",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/with-biometrics', name: 'api_create_participant_with_bio', methods: ['POST'])]
    public function createWithBiometrics(
        Request $request,
        WorkshopRepository $workshopRepo
    ): JsonResponse 
    {

        $operation = "Création d'un participant";
        
        try {

            $authResult = $this->checkAuthentication($request);
            
            if (!$authResult['status']) {
                return $this->error(
                    $authResult['message'],
                    [],
                    $operation,
                    $authResult['code']
                );

            }

            $authUsername = $authResult['user']->getUsername();
            
            // Récupération et décodage des données JSON
            $data = $this->getJsonData($request);

            
            // Vérification des champs requis
            $validationMessages = $this->validateObject(new ParticipantCreationPayload(
                $data['firstname'] ?? '',
                $data['lastname'] ?? '',
                $data['gender'] ?? '',
                $data['middlename'] ?? '',
                $data['phone'] ?? '',
                $data['phoneEMoney'] ?? '',
                $data['biometrics'] ?? ''
            ));

            if (count($validationMessages) > 0) {
                
                return $this->error(
                    "Données invalides",
                    $validationMessages,
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Récupération de l'utilisateur
            $user = $this->userRepo->findOneBy(['username' => $authUsername]);

            if (!$user) {
                return $this->error(
                    "Authentification échouée",
                    [],
                    $operation,
                    Response::HTTP_UNAUTHORIZED
                );
            }

    
            // Création de l'entité Biometric (empreintes et visage)
            $biometric = new Biometric();
            $biometric->setCreatedBy($user);
    
    
            if (!empty($data['biometrics']) && is_array($data['biometrics'])) {
                foreach ($data['biometrics'] as $bio) {
                    if (!isset($bio['pos']) || !isset($bio['data'])) continue;
    
                    // correspondance pos → champ de Biometric
                    switch ($bio['pos']) {
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
    
    
            // Création du Participant
            $participant = new Participant();
            $participant->setFirstname($data['firstname']);
            $participant->setLastname($data['lastname']);
            $participant->setMiddlename($data['middlename'] ?? null);
            $participant->setGender($data['gender']);
            $participant->setPhone($data['phone']);
            $participant->setPhoneMobileMoney($data['phoneEMoney'] ?? $data['phone']);
            $participant->setBiometric($biometric);
            $participant->setCreatedBy($user);


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
                    $participation->setCreatedBy($user);
                    $this->em->persist($participation);
                }
            }
    
            // Persistance en base
            $this->em->persist($biometric);
            $this->em->persist($participant);
            $this->em->flush();
    
            // Réponse
            return $this->success(
                [],
                $operation,
                Response::HTTP_CREATED
            );
    
        } catch (\Exception $e) {
            return $this->error(
                "Une erreur est survenue lors de la création du participant",
                [$e->getMessage()],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    
    

    #[Route('/link-to-workshop', name: 'api_link_participant_to_workshop', methods: ['POST'])]
    public function linkToWorkshop(
        Request $request,
        EntityManagerInterface $entityManager,
        WorkshopRepository $workshopRepo
    ): JsonResponse
    {

        $operation = "Ajout du participant à l'atelier";

        try {
            
            $authResult = $this->checkAuthentication($request);
            
            if (!$authResult['status']) {
                return $this->error(
                    $authResult['message'],
                    [],
                    $operation,
                    $authResult['code']
                );

            }

            $authUsername = $authResult['payload']['username'];
            $user = $this->userRepo->findOneBy(['username' => $authUsername]);

            if (!$user) {
                return $this->error(
                    "Authentification échouée",
                    [],
                    $operation,
                    Response::HTTP_UNAUTHORIZED
                );
            }
            
            // Récupération et décodage des données JSON
            $data = $this->getJsonData($request);

            // Validation
            $validationMessages = $this->validateObject(new ParticipantLinkPayload(
                $data['participantId'] ?? '',
                $data['workshopId'] ?? ''
            ));

            if (count($validationMessages) > 0) {
                
                return $this->error(
                    "Données invalides",
                    $validationMessages,
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }


             // Lier le participant à l'atelier
            $participant = $this->repo->findOneBy(['id' => $data['participantId']]);
            $workshop = $workshopRepo->findOneBy(['id' => $data['workshopId']]);

            if (!$participant) {
                return $this->error(
                    "Participant non trouvé",
                    [],
                    $operation,
                    Response::HTTP_NOT_FOUND
                );
                
            }

            if (!$workshop) {
                return $this->error(
                    "Atelier non trouvé",
                    [],
                    $operation,
                    Response::HTTP_NOT_FOUND
                );
            }

            foreach ($workshop->getWorkshopDays() as $day) {
                $participation = new Participation();
                $participation->setParticipant($participant);
                $participation->setWorkshop($workshop);
                $participation->setDay($day);
                $participation->setCreatedBy($user);
                $entityManager->persist($participation);
            }

            $entityManager->flush();

            return $this->success(
                [],
                "Participant ajouté à l'atelier avec succès",
                Response::HTTP_OK
            );

        } catch (\Throwable $e) {
            return $this->error(
                "Une erreur est survenue lors de l'ajout du participant à l'atelier",
                [$e->getMessage()],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }


       
    }
    

    #[Route('/set-attendance', name: 'api_set_participant_attendance', methods: ['POST'])]
    public function setAttendance(
        Request $request,
        WorkshopDayRepository $workshopDayRepo,
        ParticipationRepository $participationRepo,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {

        $operation = "Mise à jour de la présence du participant";
        try {

            
            // Récupération et décodage des données JSON
            $data = $this->getJsonData($request);

            // Validation
            $validationMessages = $this->validateObject(new ParticipantLinkPayload(
                $data['participantId'] ?? '',
                $data['workshopDayId'] ?? ''
            ));

            if (count($validationMessages) > 0) {
                
                return $this->error(
                    "Données invalides",
                    $validationMessages,
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }

            $participant = $this->repo->findOneBy(['id' => $data['participantId']]);
            $workshopDay = $workshopDayRepo->findOneBy(['id' => $data['workshopDayId']]);

            

            if (!$participant || !$workshopDay) {
                return $this->error(
                    "Participant ou jour d'atelier non trouvé",
                    [],
                    $operation,
                    Response::HTTP_NOT_FOUND
                );
            }

            $participation = $participationRepo->findOneByParticipantAndDay($workshopDay, $participant);

            //dd($participation);

            if (!$participation) {
                return $this->error(
                    "Aucune participation trouvée pour le participant et le jour d'atelier spécifiés",
                    [],
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }
    
            $participation->setIsPresent(true);
            $participation->setUpdatedAt(new \DateTime());

            $entityManager->persist($participation);
            $entityManager->flush();
    
    
            return $this->success(
                [],
                "Présence du participant mise à jour avec succès",
                Response::HTTP_OK
            );

        } catch (\Exception $e) {
            
            return $this->error(
                "Une erreur est survenue lors de la mise à jour de la présence du participant",
                [$e->getMessage()],
                $operation,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
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
                return $this->error(
                    "Jour d'atelier non trouvé",
                    [],
                    "Récupération de la liste des participants par jour d'atelier",
                    Response::HTTP_NOT_FOUND
                );
            }

            $attendances = $participationRepo->findActivePerWorkshopDay($workshopDay);
            $participantsArray = [];

            foreach ($attendances as $attendance) {
                $participant = $attendance->getParticipant();

                $participantsArray[] = [
                    'id' => $participant->getId(),
                    'phone' => $participant->getPhoneMobileMoney(),
                    'fullname' => $participant->getFullname(),
                    'isPresent' => $attendance->isPresent(),
                    'attendanceId' => $attendance->getId(),
                ];
            }
            

            return new JsonResponse($participantsArray, Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return $this->error(
                "Une erreur est survenue lors de la récupération de la liste des participants par jour d'atelier",
                [$e->getMessage()],
                "Récupération de la liste des participants par jour d'atelier",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    #[Route('/details', name: 'api_get_participant_details', methods: ['POST'])]
    public function getDetails(
        Request $request,
    ): JsonResponse
    {
        $operation = "Récupération des détails du participant";
        try {

            $authResult = $this->checkAuthentication($request);
            
            if (!$authResult['status']) {
                return $this->error(
                    $authResult['message'],
                    [],
                    $operation,
                    $authResult['code']
                );

            }
            
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->error(
                    "Données JSON invalides",
                    [],
                    $operation,
                    Response::HTTP_BAD_REQUEST
                );
            }
        
            // Vérification des champs requis
            $required = ['participantId'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->error(
                        "Le champ '$field' est obligatoire",
                        [],
                        $operation,
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            $participant = $this->repo->findOneBy(['id' => $data['participantId']]);

            

            if (!$participant ) {
                return $this->error(
                    "Participant non trouvé",
                    [],
                    $operation,
                    Response::HTTP_NOT_FOUND
                );
            }

            // Création de l'entité Participant
    
            return new JsonResponse([
                'id' => $participant->getId(),
                'phoneMobileMoney' => $participant->getPhoneMobileMoney(),
                'fullname' => $participant->getFullname(),
                'biometric' => $participant->getBiometric() ? $participant->getBiometric()->getFingers() : null
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la récupération : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
