<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;

class ExceptionListener
{
    private TransportInterface $mailer;
    private LoggerInterface $logger;
    private KernelInterface $kernel;
    private RequestStack $requestStack;

    public function __construct(
        TransportInterface $mailer,
        LoggerInterface    $logger,
        KernelInterface    $kernel,
        RequestStack       $requestStack,
    )
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->kernel = $kernel;
        $this->requestStack = $requestStack;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() <= 404) {
            return;
        }

        if ($this->kernel->getEnvironment() == 'prod') {
            $this->sendErrorEmail($exception);
            $this->logger->error($exception->getMessage());

            $request = $this->requestStack->getCurrentRequest();
            if ($request && $request->headers->has('referer')) {
                $referer = $request->headers->get('referer');

                $response = new RedirectResponse($referer);
                $event->setResponse($response);
            }
        }
    }

    private function sendErrorEmail($exception)
    {

        $email = (new TemplatedEmail())
            ->from(new Address($_ENV['SENDER_EMAIL'],'System integrity'))
            ->to('rubuz.l@infosetgroup.com')
            ->subject('APPLICATION INTEGRITY [WORKSHOP API ENABEL]')
            ->htmlTemplate('email/error.html.twig')
            ->context([
                'message' => $exception->getMessage(),
                '_date' => date_create(),
                'trace' => $exception->getTraceAsString(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception|TransportExceptionInterface $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}