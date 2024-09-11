<?php

namespace App\EventListener;

use App\Service\Logger;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventListener implements EventSubscriberInterface
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => 'onConsoleError',
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $error = $event->getError();
        $command = $event->getCommand();

        $data = [
            'message' =>
                sprintf(
                    '[%s] A critical error occurred during the execution of command : %s',
                    $command ? $command->getName() : 'unknown',
                    $error->getMessage()
                ),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
            '@timestamp' => date('c')
        ];

        $this->logger->log($data, 'ERROR');
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $data = [
            'message' =>
                sprintf(
                    'HTTP REQUEST : [%s] %s',
                    $request->getMethod(),
                    $request->getUri()
                ),
            'query_parameters' => $request->query->all(),
            'request_body' => $request->getContent(),
            'client_ip' => $request->getClientIp(),
            '@timestamp' => date('c')
        ];

        $this->logger->log($data, 'DEBUG');
    }
}
