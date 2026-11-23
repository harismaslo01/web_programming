<?php

use OpenApi\Attributes as OA;

class DronePaths
{
    #[OA\Get(
        path: '/drones',
        tags: ['Drones'],
        summary: 'List all drones',
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Searches brand, model, category and serial number', schema: new OA\Schema(type: 'string'), example: 'mavic'),
            new OA\Parameter(name: 'status', in: 'query', required: false, description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['available', 'reserved', 'maintenance', 'retired'])),
            new OA\Parameter(name: 'category', in: 'query', required: false, description: 'Filter by category', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'brand', in: 'query', required: false, description: 'Filter by brand', schema: new OA\Schema(type: 'string'), example: 'DJI')
        ],
        responses: [
            new OA\Response(response: 200, description: 'List', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Drone'))),
            new OA\Response(response: 400, description: 'Invalid query parameter', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getAll(): void
    {
    }

    #[OA\Get(
        path: '/drones/{id}',
        tags: ['Drones'],
        summary: 'Get one drone',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: 'Found', content: new OA\JsonContent(ref: '#/components/schemas/Drone')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getById(): void
    {
    }

    #[OA\Post(
        path: '/drones',
        tags: ['Drones'],
        summary: 'Create a drone',
        description: 'Serial number must be unique (409).',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DroneInput')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Drone')),
            new OA\Response(response: 400, description: 'Invalid data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Conflict with existing data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function create(): void
    {
    }

    #[OA\Put(
        path: '/drones/{id}',
        tags: ['Drones'],
        summary: 'Update a drone',
        description: 'Send only the fields that change.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DroneInput')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Drone')),
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
        path: '/drones/{id}',
        tags: ['Drones'],
        summary: 'Delete a drone',
        description: 'A drone assigned to orders cannot be deleted (409).',
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
}
