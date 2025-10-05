<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Repository\BiometricRepository;
use App\Repository\DemographicRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



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
        // plus all the dependencies of the parent class
        ...$dependencies
    )
    {
        parent::__construct(...$dependencies);
        $this->repo = $repo_;
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
        EntityManagerInterface $entityManager
    ): JsonResponse
    {

        // Validation



        // Création de l'entité Participant

        return new JsonResponse([
            'message' => 'Participant créé avec succès'
        ], Response::HTTP_OK);
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
}
