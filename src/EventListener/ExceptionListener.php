<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use App\Service\Logger;

class ExceptionListener
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if ($_ENV['APP_ENV'] == 'dev') {
            return;
        }

        $exception = $event->getThrowable();

        $this->logger->error($exception);

        if ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse([
                "error" => array(
                    "code" => $exception->getStatusCode(),
                    "message" => "",
                    "details" => $exception->getMessage()
                ),
            ]);
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
            $response->headers->set('Content-Type', 'application/json');
        } else {
            $response = new JsonResponse([
                "error" => array(
                    "code" => 500,
                    "message" => "",
                    "details" => $exception->getMessage()
                ),
            ]);
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $response->headers->set('Content-Type', 'application/json');
        }

        // sends the modified response object to the event
        $event->setResponse($response);
    }
}