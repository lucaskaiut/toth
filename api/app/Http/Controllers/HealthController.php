<?php

namespace App\Http\Controllers;

use App\Services\Health\ApplicationHealthChecker;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HealthController
{
    public function __invoke(ApplicationHealthChecker $healthChecker): JsonResponse
    {
        $result = $healthChecker->run();

        $status = $result['healthy'] ? 'healthy' : 'unhealthy';
        $httpStatus = $result['healthy'] ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return response()->json([
            'status' => $status,
            'checks' => $result['checks'],
        ], $httpStatus);
    }
}
