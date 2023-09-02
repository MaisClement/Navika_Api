<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if ( $_ENV['APP_ENV'] == 'dev' ) {
            return;
        }
        
        $exception = $event->getThrowable();

        $json = array(
            "error" => array(
                "code" => $exception->getStatusCode(),
                "message" => "",
                "details" => $exception->getMessage()
            ),
        );
        $message = json_encode($json);

        $headers = $exception->getHeaders();
        $headers['Content-Type'] = 'application/json';
        
        $response = new Response();
        $response->setContent($message);
        
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($headers);
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}