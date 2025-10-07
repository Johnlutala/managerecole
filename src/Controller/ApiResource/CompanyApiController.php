<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\Biometric;
use App\Entity\Company;
use App\Entity\Demographic;
use App\Entity\Participant;
use App\Repository\BiometricRepository;
use App\Repository\CompanyRepository;
use App\Repository\DemographicRepository;
use App\Repository\ParticipantRepository;
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



#[Route('/api/rest/v1/companies')]
final class CompanyApiController extends AbstractApiController
{

    private CompanyRepository $repo;

    public function __construct(
        CompanyRepository $repo_,
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
    }


    #[Route('', name: 'api_get_enabled_companies', methods: ['GET'])]
    public function getCompanies(
        Request $request
    ): JsonResponse
    {
        try {
            $companies = $this->repo->findEnabled();
            $data = $this->serializer->serialize($companies, 'json', ['groups' => 'company:read']);
            $companies = json_decode($data, true);
            
            //dd($companies);

            return new JsonResponse(
                $companies,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            
            return new JsonResponse(
                [
                    'code'=> "2",
                    'message' => 'Une erreur est survenue ' . $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
