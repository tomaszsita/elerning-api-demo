<?php

namespace App\EventListener;

use App\Exception\HttpExceptionMapping;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        // Jeśli to już HttpException, nie zmieniaj
        if ($exception instanceof HttpExceptionInterface) {
            return;
        }

        // Mapuj custom exceptions na HTTP status codes
        $statusCode = HttpExceptionMapping::getStatusCode($exception);
        $message = HttpExceptionMapping::getErrorMessage($exception);

        $response = new JsonResponse([
            'error' => $message,
            'status' => $statusCode
        ], $statusCode);

        $event->setResponse($response);
    }
}
