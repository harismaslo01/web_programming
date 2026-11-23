<?php

use OpenApi\Attributes as OA;

class HealthPaths
{
    #[OA\Get(
        path: '/',
        tags: ['Health'],
        summary: 'API status',
        responses: [
            new OA\Response(response: 200, description: 'The API is running', content: new OA\JsonContent(type: 'object', example: ['app' => 'DronePilot Services API', 'message' => 'The backend is running.']))
        ]
    )]
    public function status(): void
    {
    }
}
