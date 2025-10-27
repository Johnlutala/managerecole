<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\Company;
use App\Repository\BiometricRepository;
use App\Repository\CompanyRepository;
use App\Repository\DemographicRepository;
use App\Repository\UserRepository;
use App\Service\TokenEncoder;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
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
        // Dependencies from AbstractApiController
        SerializerInterface $serializer,
        HttpClientInterface $httpClient,
        TransportInterface $mailer,
        LoggerInterface $logger,
        LoggerInterface $handshakeLogger,
        ValidatorInterface $validator,
        UserRepository  $userRepo
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator, $userRepo);
        $this->repo = $repo_;
        $this->userRepo = $userRepo;
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

    #[Route('', name: 'api_create_company', methods: ['POST'])]
    public function createCompany(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse
    {

        try {
            
            $data = json_decode($request->getContent(), true);

            // Vérification de l existence des données
            if (!$data) {
                
                return new JsonResponse(
                    [
                        'code'=> "1",
                        'message' => 'Données manquantes ou invalides'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérification des champs obligatoires
            if (!isset($data['name']) || empty(trim($data['name']))) {
                
                return new JsonResponse(
                    [
                        'code'=> "1",
                        'message' => 'Le champs name est obligatoire'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Vérification unicité du nom
            $existingCompany = $this->repo->findOneBy(['name' => $data['name']]);
            if ($existingCompany) {
                return new JsonResponse(
                    [
                        'code'=> "1",
                        'message' => 'Une entreprise avec ce nom existe déjà'
                    ],
                    Response::HTTP_CONFLICT
                );
            }

            $company = new Company();
            $company->setName($data['name']);

            if (isset($data['description'])) {
                $company->setDescription($data['description']);
            }
    
            $em->persist($company);
            $em->flush();

            return new JsonResponse(
                [
                    'code'=> "0",
                    'message' => 'Entreprise ' . $data['name'] . ' créée avec succès',
                ],
                Response::HTTP_CREATED
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
