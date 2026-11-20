<?php

use OpenApi\Attributes as OA;

class OrderPaths
{
    #[OA\Get(
        path: '/orders',
        tags: ['Orders'],
        summary: 'List all orders',
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'query', required: false, description: 'Only orders of this user', schema: new OA\Schema(type: 'integer'), example: 2)
        ],
        responses: [
            new OA\Response(response: 200, description: 'List', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Order'))),
            new OA\Response(response: 400, description: 'Invalid query parameter', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getAll(): void
    {
    }

    #[OA\Get(
        path: '/orders/{id}',
        tags: ['Orders'],
        summary: 'Get one order',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: 'Found', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getById(): void
    {
    }

    #[OA\Post(
        path: '/orders',
        tags: ['Orders'],
        summary: 'Create a order',
        description: 'Required fields: user_id, service_id, title, location, scheduled_date. order_number, total_price (service price) and status (pending) are filled in automatically when missing. Business rules: user must be active, service active, drone not in maintenance/retired, date not in the past. WEATHER CHECK: if the Open-Meteo forecast for the location shows rain, snow, fog, thunderstorm, wind above 38 km/h or gusts above 43 km/h in the 3 hours from scheduled_date, the order is refused with 400 and the message "Drones do not fly in this weather...". Dates more than 7 days ahead are not checked. If the weather API is down, the order is accepted and the failure is logged.',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/OrderInput')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 400, description: 'Invalid data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Conflict with existing data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function create(): void
    {
    }

    #[OA\Put(
        path: '/orders/{id}',
        tags: ['Orders'],
        summary: 'Update a order',
        description: 'Send only the fields that change. Completed and cancelled orders cannot be changed (409). Changing location or scheduled_date repeats the weather check.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/OrderInput')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 400, description: 'Invalid data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Conflict with existing data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/orders/{id}',
        tags: ['Orders'],
        summary: 'Delete a order',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted', content: new OA\JsonContent(ref: '#/components/schemas/Message')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Still in use', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function delete(): void
    {
    }

    #[OA\Get(
        path: '/orders/{id}/media',
        tags: ['Orders'],
        summary: 'Media of one order',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: 'List', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Media'))),
            new OA\Response(response: 404, description: 'Order not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getMedia(): void
    {
    }
}
