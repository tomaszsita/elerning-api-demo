<?php

declare(strict_types = 1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController
{
    #[Route('/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status'    => 'ok',
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'version'   => '1.0.0',
        ], Response::HTTP_OK);
    }
}
