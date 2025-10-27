<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Entity\User;
use App\Repository\MerchantConfigurationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;



#[Route('/mail')]
final class EmailController extends AbstractApiController
{


    public function __construct(
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
    }


    #[Route('/test', name: 'send_mail', methods: ['GET'])]
    public function notifyAllByMail(
        TransportInterface  $mailer,
        LoggerInterface $logger,
        Request $request
    ) : JsonResponse
    {
        
        try {
            
            /* $recipients = [
                'annie.lebughe@enabel.be',
                'francois-xavier.kabala@enabel.be',
                'fifi.esalo@enabel.be',
                'tresor.mutombo@enabel.be',
                'don.bungiena@enabel.be',
            ]; */

            $recipients = [
                "rubuz.l@infosetgroup.com"
            ];

            $numbers = [
                [
                    'key' => '243839412821',
                    'value' => 'Receiver invalid or not allowed to receive this type of transaction',
                ],
                [
                    'key' => '243839090032',
                    'value' => 'Receiver invalid or not allowed to receive this type of transaction',
                ],
                [
                    'key' => '243824338426',
                    'value' => 'Receiver invalid or not allowed to receive this type of transaction',
                ]
            ];
            
            $email_notification_create_paylist = (new TemplatedEmail())
                ->from(new Address('payroll@flexpaie.com', 'FlexRoll'))
                ->to(...$recipients)
                ->subject('FlexRoll - Notification exécution')
                ->htmlTemplate('pay_list/reasons.html.twig')
                ->context([
                    'list_name' => "Liste SH5_2025_008_a; Remboursement frais de transport pour les participants de la formation des mécanismes de gestion des plaintes MGP, fait du 03 au 07102025",
                    'execution_date' => "17/10/2025",
                    'total'=> "30",
                    'success' => "27",
                    'failed' => "3",
                    'numbers' => $numbers,
                ]);

            $mailer->send($email_notification_create_paylist);

            return new JsonResponse([
                'code' => '0',
                'message' => 'Mail envoyé avec succès',
            ], Response::HTTP_OK);

            
        } catch (\Exception|TransportExceptionInterface $e) {
            $logger->critical($e->getMessage());

            return new JsonResponse([
                'code' => '1',
                'message' => 'Erreur lors de l\'envoi du mail: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }


}
