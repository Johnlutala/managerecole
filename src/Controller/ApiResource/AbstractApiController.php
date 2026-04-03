<?php

namespace App\Controller\ApiResource;


use App\Service\JwtTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractApiController extends AbstractController
{
    protected SerializerInterface $serializer;
    protected HttpClientInterface $http;
    protected TransportInterface $mailer;
    protected LoggerInterface $logger;
    protected ValidatorInterface $validator;
    protected JwtTokenService $jwt;
    protected EntityManagerInterface $em;

    #[Required]
    public function setUpDependencies(
        EntityManagerInterface $em_,
        LoggerInterface $logger_,
        ValidatorInterface $validator_,
        JwtTokenService $jwt_,
        HttpClientInterface $http_,
        TransportInterface $mailer_,
        SerializerInterface $serializer_
    ): void
    {
        $this->em = $em_;
        $this->logger = $logger_;
        $this->http = $http_;
        $this->validator = $validator_;
        $this->jwt = $jwt_;
        $this->mailer = $mailer_;
        $this->serializer = $serializer_;
    }

    public function checkAuthentication(
        Request $request
    ) : array {
        
        $token = $this->jwt->extractTokenFromRequest($request);

        if (!$token) {
            
            return [
                "status" => false,
                "code" => Response::HTTP_UNAUTHORIZED,
                "message" => "Token manquant ou invalide"
            ];
        }

        $payload = $this->jwt->validateAndDecode($token);

        if (count($payload) === 1 || isset($payload['error'])) {
            
            return [
                "status" => false,
                "code" => Response::HTTP_FORBIDDEN,
                "message" => $payload['error']
            ];
        }

        return [
            "status" => true,
            "code" => Response::HTTP_OK,
            "payload" => $payload
        ];
    }

    /**
     * Validate DTO / Entity
     */
    public function validateObject(object $object): array
    {
        $errors = $this->validator->validate($object);

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }

        return $messages;
    }


    public function success(mixed $data, string $operation, int $status=200): JsonResponse
    {

        $this->logger->info(
            "tag : " . $operation,
            $data
        );

        return $this->json([
            'code' => "0",
            'message' => "Succès",
            'data' => $data
        ], $status);
    }


    public function error(string $message, mixed $data, string $operation, int $status): JsonResponse
    {
        $this->logger->error(
            "tag : " . $operation,
            $data
        );
        
        return $this->json([
            'code' => "1",
            'message' => $message,
            'errors' => $data
        ], $status);
    }

    public function getJsonData(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }

}