<?php

use OpenApi\Attributes as OA;

class MediaPaths
{
    #[OA\Get(
        path: '/media',
        tags: ['Media'],
        summary: 'List all media files',
        parameters: [
            new OA\Parameter(name: 'order_id', in: 'query', required: false, description: 'Only media of this order', schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: 'List', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Media'))),
            new OA\Response(response: 400, description: 'Invalid query parameter', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getAll(): void
    {
    }

    #[OA\Get(
        path: '/media/{id}',
        tags: ['Media'],
        summary: 'Get one media file',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: 'Found', content: new OA\JsonContent(ref: '#/components/schemas/Media')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getById(): void
    {
    }

    #[OA\Post(
        path: '/media',
        tags: ['Media'],
        summary: 'Create a media file',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MediaInput')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Media')),
            new OA\Response(response: 400, description: 'Invalid data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Conflict with existing data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function create(): void
    {
    }

    #[OA\Put(
        path: '/media/{id}',
        tags: ['Media'],
        summary: 'Update a media file',
        description: 'Send only the fields that change.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MediaInput')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Media')),
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
        path: '/media/{id}',
        tags: ['Media'],
        summary: 'Delete a media file',
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
