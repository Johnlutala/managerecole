<?php

namespace App\Controller\ApiResource;

use App\Controller\ApiResource\AbstractApiController;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
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
        ValidatorInterface $validator,
        UserRepository $userRepository
    )
    {
        parent::__construct($serializer, $httpClient, $mailer, $logger, $handshakeLogger, $validator, $userRepository);
    }


    #[Route('/test', name: 'send_mail', methods: ['GET'])]
    public function notifyAllByMail(
        TransportInterface  $mailer,
        LoggerInterface $logger,
        Request $request
    ) : JsonResponse
    {
        
        try {
            
            /* $recipients = [ // ENABEL_K
                'annie.lebughe@enabel.be',
                'francois-xavier.kabala@enabel.be',
                'fifi.esalo@enabel.be',
                'tresor.mutombo@enabel.be',
                'don.bungiena@enabel.be',
            ]; */

            /* $recipients = [ //E_KORLOM
                "rose.musau@enabel.be",
                "pierre.esokowa@enabel.be",
                "victoire.kabambi@enabel.be",
                "gado.moussadododan@enabel.be",
                "pierre.onema@enabel.be",
            ]; */

            $recipients = [
                "rubuz.l@infosetgroup.com"
            ];

            $numbers = [
                [
                    'key' => '243857610635',
                    'value' => 'Le bénéficiaire n\'est pas eligible',
                ],
                /* [
                    'key' => '243843395447',
                    'value' => 'Le bénéficiaire n\'est pas eligible',
                ],
                [
                    'key' => '243833288642',
                    'value' => 'Receiver invalid or not allowed to receive this type of transaction',
                ],
                [
                    'key' => '243822871528',
                    'value' => 'Receiver invalid or not allowed to receive this type of transaction',
                ],
                [
                    'key' => '243831402879',
                    'value' => 'Receiver invalid or not allowed to receive this type of transaction',
                ],
                [
                    'key' => '243815050798',
                    'value' => 'Receiver invalid or not allowed to receive this type of transaction',
                ], */
            ];
            
            $email_notification_create_paylist = (new TemplatedEmail())
                ->from(new Address('payroll@flexpaie.com', 'FlexRoll'))
                ->to(...$recipients)
                ->subject('FlexRoll - Notification exécution')
                ->htmlTemplate('pay_list/reasons.html.twig')
                ->context([
                    'merchant_name' => "E_KORLOM",
                    'list_name' => "Remboursement frais de transport pour les participants de à l'atelier du Diagnostic Agraire 1630,62$",
                    'execution_date' => "14/11/2025",
                    'total'=> "41",
                    'success' => "40",
                    'failed' => "1",
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
