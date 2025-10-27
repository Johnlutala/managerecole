<?php

namespace App\Controller\ApiResource;

//use App\Service\ApiCall;
//use App\Service\PDF;

use App\Service\TokenGeneration;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractApiController extends AbstractController
{
    protected SerializerInterface $serializer;
    protected HttpClientInterface $httpClient;
    protected TransportInterface $mailer;
    protected LoggerInterface $logger;
    protected LoggerInterface $handshakeLogger;
    protected ValidatorInterface $validator;
    //protected PDF $pdf;
    //protected ApiCall $apiCall;

    public function __construct(
        SerializerInterface $serializer_,
        HttpClientInterface $httpClient_,
        TransportInterface  $mailer_,
        LoggerInterface     $logger_,
        LoggerInterface     $handshakeLogger_,
        ValidatorInterface  $validator_
        //PDF                 $pdf_,
        //ApiCall             $apiCall_
    )
    {
        $this->serializer = $serializer_;
        $this->mailer = $mailer_;
        $this->logger = $logger_;
        $this->httpClient = $httpClient_;
        $this->handshakeLogger = $handshakeLogger_;
        $this->validator = $validator_;
        //$this->pdf = $pdf_;
        //$this->apiCall = $apiCall_;
    }

    protected function notFoundException($message = "Not found"): JsonResponse
    {
        return new JsonResponse([
            'message' => $message
        ], Response::HTTP_NOT_FOUND);
    }

    protected function badRequestException($message = "Bad request"): JsonResponse
    {
        return new JsonResponse([
            'message' => $message
        ], Response::HTTP_BAD_REQUEST);
    }

}