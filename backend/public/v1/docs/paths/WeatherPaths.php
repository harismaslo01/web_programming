<?php

use OpenApi\Attributes as OA;

class WeatherPaths
{
    #[OA\Get(
        path: '/weather/flight-conditions',
        tags: ['Weather'],
        summary: 'Current drone flight conditions',
        description: 'Calls Open-Meteo (timeout 5 s). Send city OR latitude and longitude.',
        parameters: [
            new OA\Parameter(name: 'city', in: 'query', required: false, description: 'City or place name', schema: new OA\Schema(type: 'string'), example: 'Sarajevo'),
            new OA\Parameter(name: 'latitude', in: 'query', required: false, description: 'Latitude (-90 to 90)', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'longitude', in: 'query', required: false, description: 'Longitude (-180 to 180)', schema: new OA\Schema(type: 'number'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Current weather and flight verdict', content: new OA\JsonContent(ref: '#/components/schemas/FlightConditions')),
            new OA\Response(response: 400, description: 'Missing or invalid parameters', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Location not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 503, description: 'Weather service unavailable', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function flightConditions(): void
    {
    }

    #[OA\Get(
        path: '/weather/forecast',
        tags: ['Weather'],
        summary: 'Daily drone flight forecast',
        description: 'Daily forecast from Open-Meteo with a flight verdict for each day. Used by the booking form to show which days are suitable.',
        parameters: [
            new OA\Parameter(name: 'city', in: 'query', required: true, description: 'City or place name', schema: new OA\Schema(type: 'string'), example: 'Sarajevo'),
            new OA\Parameter(name: 'days', in: 'query', required: false, description: 'Number of days (1-16)', schema: new OA\Schema(type: 'integer'), example: 5)
        ],
        responses: [
            new OA\Response(response: 200, description: 'Forecast', content: new OA\JsonContent(ref: '#/components/schemas/Forecast')),
            new OA\Response(response: 400, description: 'Missing or invalid parameters', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Location not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 503, description: 'Weather service unavailable', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function forecast(): void
    {
    }
}
