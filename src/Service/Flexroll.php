<?php

namespace App\Service;

use App\Entity\Workshop;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Flexroll
{
    private HttpClientInterface  $httpClient;
    const FLEXROLL_API_URL = 'https://api.flexroll.com/v1/attendance/batch';
    const DEV_URL = 'https://api.flexroll.com/v1/attendance/batch';


    public function __construct(
        HttpClientInterface $httpClient, 
        WorkshopDayRepository $dayRepository
    )
    {
        $this->httpClient = $httpClient;
    }


    public function sendList(
        array $datas
    ): JsonResponse
    {
        
        try {
            
            $response = $this->httpClient->request(
                'POST',
                'https://api.flexroll.com/v1/attendance/batch',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($datas),
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode === Response::HTTP_OK) {
                return new JsonResponse([
                    'code' => '0',
                    'message' => 'Liste envoyée avec succès vers Flexroll',
                ]);
            }

            return new JsonResponse([
                'code' => '1',
                'message' => 'Erreur lors de l\'envoi de la liste vers Flexroll',
            ], $statusCode);

        } catch (\Exception $e) {
            
            return new JsonResponse([
                'code' => '2',
                'message' => 'Impossible d\'envoyer la liste vers Flexroll: ' . $e->getMessage(),
            ], 500);
        }
    }

}
