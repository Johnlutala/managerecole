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
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
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
        WorkshopRepository $workshopRepo
    ): JsonResponse {
        try {
            // Récupération et décodage des données JSON
            $data = json_decode($request->getContent(), true);
    
            if (!$data) {
                return new JsonResponse(['error' => 'Données JSON invalides'], Response::HTTP_BAD_REQUEST);
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
        EntityManagerInterface $entityManager
    ): JsonResponse
    {

        try {
            
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                    return new JsonResponse([
                        'code' => "1",
                        'message' => 'Données JSON invalides'
                    ], Response::HTTP_BAD_REQUEST);
                }
        
            // Vérification des champs requis
            $required = ['participantId', 'workshopId', 'workshopDayId', 'biometrics'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse([
                        'code' => "1",
                        'message' => "Le champ '$field' est obligatoire"
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $participant = $this->repo->findOneBy(['id' => $data['participantId']]);
            $workshopDayId = $workshopDayRepo->findOneBy(['id' => $data['workshopDayId']]);
    
    
    
            // Création de l'entité Participant
    
            return new JsonResponse([
                'message' => 'Participant créé avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => "2",
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
