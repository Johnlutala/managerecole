<?php

namespace App\Service;

use App\Entity\Workshop;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class Flexroll
{
    private HttpClientInterface  $httpClient;
    private LoggerInterface $logger;

    const FLEXROLL_API_URL = 'https://flexroll.flexpay.cd/api/v1/rest/workshop/list/upload';
    const DEV_URL = "https://grumpy-walls-shout.loca.lt/api/v1/rest/workshop/list/upload";

    public function __construct(
        HttpClientInterface $httpClient, 
        WorkshopDayRepository $dayRepository,
        LoggerInterface $logger
    )
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }


    public function sendList(
        array $datas
    ): JsonResponse
    {
        //$this->logger->info('Envoi de la liste vers Flexroll', ['data' => $datas]);
        try {

            //dump ($datas);
            $response = $this->httpClient->request(
                'POST',
                $this::FLEXROLL_API_URL,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($datas),
                ]
            );

            //dump($response);

            $statusCode = $response->getStatusCode();

            if ($statusCode === Response::HTTP_OK) {
                return new JsonResponse([
                    'code' => '0',
                    'message' => 'Liste envoyée avec succès vers Flexroll',
                ]);
            }

            $this->logger->error('Erreur lors de l\'envoi de la liste vers Flexroll: ' . $response->getContent(false));

            return new JsonResponse([
                'code' => '1',
                'message' => 'Erreur lors de l\'envoi de la liste vers Flexroll',
            ], $statusCode);

        } catch (\Exception $e) {

            //$this->logger->error('Erreur lors de l\'envoi de la liste vers Flexroll: ' . $e->getMessage());

            return new JsonResponse([
                'code' => '2',
                'message' => 'Impossible d\'envoyer la liste vers Flexroll: ' . $e->getMessage(),
            ], 500);
        }
    }

}
