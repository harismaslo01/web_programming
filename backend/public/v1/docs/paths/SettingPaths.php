<?php

use OpenApi\Attributes as OA;

class SettingPaths
{
    #[OA\Get(
        path: '/settings',
        tags: ['Settings'],
        summary: 'List all settings',
        responses: [
            new OA\Response(response: 200, description: 'List', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting'))),
            new OA\Response(response: 400, description: 'Invalid query parameter', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getAll(): void
    {
    }

    #[OA\Get(
        path: '/settings/{id}',
        tags: ['Settings'],
        summary: 'Get one setting',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: 'Found', content: new OA\JsonContent(ref: '#/components/schemas/Setting')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getById(): void
    {
    }

    #[OA\Post(
        path: '/settings',
        tags: ['Settings'],
        summary: 'Create a setting',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SettingInput')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Setting')),
            new OA\Response(response: 400, description: 'Invalid data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Conflict with existing data', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function create(): void
    {
    }

    #[OA\Put(
        path: '/settings/{id}',
        tags: ['Settings'],
        summary: 'Update a setting',
        description: 'Send only the fields that change.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SettingInput')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(ref: '#/components/schemas/Setting')),
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
        path: '/settings/{id}',
        tags: ['Settings'],
        summary: 'Delete a setting',
        description: 'site_name, contact_email and default_currency cannot be deleted (409).',
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
        path: '/settings/key/{key}',
        tags: ['Settings'],
        summary: 'Get a setting by key',
        parameters: [
            new OA\Parameter(name: 'key', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'default_currency')
        ],
        responses: [
            new OA\Response(response: 200, description: 'Found', content: new OA\JsonContent(ref: '#/components/schemas/Setting')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))
        ]
    )]
    public function getByKey(): void
    {
    }
}
