<?php

use OpenApi\Attributes as OA;

/*
 * General OpenAPI information and the data schemas (request/response bodies).
 * Endpoints are documented in paths/*.php, one file per route file.
 */
#[OA\Info(
    title: 'DronePilot Services API',
    description: 'REST API for drone rental and aerial services (Milestone 3). Authentication with JWT is added in Milestone 4.',
    version: '1.0.0'
)]
#[OA\Server(url: API_BASE_URL, description: 'This server')]
#[OA\Tag(name: 'Health', description: 'API status')]
#[OA\Tag(name: 'Roles', description: 'User roles')]
#[OA\Tag(name: 'Users', description: 'User accounts')]
#[OA\Tag(name: 'Services', description: 'Aerial services offered to customers')]
#[OA\Tag(name: 'Drones', description: 'Drone inventory')]
#[OA\Tag(name: 'Orders', description: 'Bookings. New bookings are checked against the weather forecast.')]
#[OA\Tag(name: 'Media', description: 'Files delivered for orders')]
#[OA\Tag(name: 'Settings', description: 'Application settings')]
#[OA\Tag(name: 'Weather', description: 'External API: Open-Meteo weather and drone flight conditions')]
#[OA\Schema(
    schema: 'Error',
    required: ['error'],
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Drone not found.')
    ]
)]
#[OA\Schema(
    schema: 'Message',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Drone deleted.')
    ]
)]
#[OA\Schema(
    schema: 'RoleInput',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 50, example: 'Admin'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: 'Full administrative access')
    ]
)]
#[OA\Schema(
    schema: 'Role',
    allOf: [
        new OA\Schema(properties: [new OA\Property(property: 'id', type: 'integer', example: 1)]),
        new OA\Schema(ref: '#/components/schemas/RoleInput')
    ]
)]
#[OA\Schema(
    schema: 'UserInput',
    properties: [
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100, example: 'Haris'),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 100, example: 'Maslo'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'haris@example.com'),
        new OA\Property(property: 'password', type: 'string', minLength: 8, writeOnly: true, description: 'Plain password, stored only as a hash and never returned', example: 'secret123'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+38761000000'),
        new OA\Property(property: 'role_id', type: 'integer', example: 2),
        new OA\Property(property: 'is_active', type: 'boolean', example: true)
    ]
)]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'first_name', type: 'string', example: 'Demo'),
        new OA\Property(property: 'last_name', type: 'string', example: 'User'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'haris@stu.ibu.edu.ba'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+38761000001'),
        new OA\Property(property: 'role_id', type: 'integer', example: 2),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', example: '2026-11-01 10:00:00'),
        new OA\Property(property: 'updated_at', type: 'string', example: '2026-11-01 10:00:00')
    ]
)]
#[OA\Schema(
    schema: 'ServiceInput',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Drone Photography'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Professional aerial photography sessions.'),
        new OA\Property(property: 'price', type: 'number', minimum: 0, example: 150.0),
        new OA\Property(property: 'duration_days', type: 'integer', minimum: 1, maximum: 365, example: 1),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'frontend/static/assets/images/sa_photo2.JPG'),
        new OA\Property(property: 'active', type: 'boolean', example: true)
    ]
)]
#[OA\Schema(
    schema: 'Service',
    allOf: [
        new OA\Schema(properties: [new OA\Property(property: 'id', type: 'integer', example: 1)]),
        new OA\Schema(ref: '#/components/schemas/ServiceInput')
    ]
)]
#[OA\Schema(
    schema: 'DroneInput',
    properties: [
        new OA\Property(property: 'brand', type: 'string', example: 'DJI'),
        new OA\Property(property: 'model', type: 'string', example: 'Mavic 3'),
        new OA\Property(property: 'category', type: 'string', nullable: true, example: 'Inspection'),
        new OA\Property(property: 'serial_number', type: 'string', example: 'DJI-M3-0001'),
        new OA\Property(property: 'status', type: 'string', enum: ['available', 'reserved', 'maintenance', 'retired'], example: 'available'),
        new OA\Property(property: 'last_service_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'battery_level', type: 'integer', minimum: 0, maximum: 100, nullable: true),
        new OA\Property(property: 'purchase_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'image_url', type: 'string', nullable: true)
    ]
)]
#[OA\Schema(
    schema: 'Drone',
    allOf: [
        new OA\Schema(properties: [new OA\Property(property: 'id', type: 'integer', example: 1)]),
        new OA\Schema(ref: '#/components/schemas/DroneInput')
    ]
)]
#[OA\Schema(
    schema: 'OrderInput',
    description: 'Required on create: user_id, service_id, title, location, scheduled_date.',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 2),
        new OA\Property(property: 'service_id', type: 'integer', example: 1),
        new OA\Property(property: 'drone_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'order_number', type: 'string', description: 'Generated automatically when omitted', example: 'DP-2026-0003'),
        new OA\Property(property: 'title', type: 'string', maxLength: 150, example: 'Wedding aerial video'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'location', type: 'string', maxLength: 150, description: 'City or place, used for the weather check', example: 'Sarajevo'),
        new OA\Property(property: 'scheduled_date', type: 'string', description: 'YYYY-MM-DD HH:MM:SS, cannot be in the past', example: '2026-11-25 10:00:00'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'pending', 'confirmed', 'in_progress', 'completed', 'cancelled'], example: 'pending'),
        new OA\Property(property: 'total_price', type: 'number', description: 'Defaults to the service price', example: 150.0),
        new OA\Property(property: 'notes', type: 'string', nullable: true)
    ]
)]
#[OA\Schema(
    schema: 'Order',
    allOf: [
        new OA\Schema(properties: [new OA\Property(property: 'id', type: 'integer', example: 1)]),
        new OA\Schema(ref: '#/components/schemas/OrderInput')
    ]
)]
#[OA\Schema(
    schema: 'MediaInput',
    properties: [
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'file_name', type: 'string', example: 'property-aerial-01.jpg'),
        new OA\Property(property: 'file_type', type: 'string', nullable: true, enum: ['image/jpeg', 'image/png', 'image/webp', 'image/tiff', 'video/mp4', 'video/quicktime', 'application/pdf', 'application/zip'], example: 'image/jpeg'),
        new OA\Property(property: 'file_url', type: 'string', example: '/uploads/orders/property-aerial-01.jpg')
    ]
)]
#[OA\Schema(
    schema: 'Media',
    allOf: [
        new OA\Schema(properties: [new OA\Property(property: 'id', type: 'integer', example: 1)]),
        new OA\Schema(ref: '#/components/schemas/MediaInput')
    ]
)]
#[OA\Schema(
    schema: 'SettingInput',
    properties: [
        new OA\Property(property: 'key_name', type: 'string', pattern: '^[a-z][a-z0-9_]*$', example: 'default_currency'),
        new OA\Property(property: 'value_text', type: 'string', nullable: true, example: 'BAM'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Currency used for prices')
    ]
)]
#[OA\Schema(
    schema: 'Setting',
    allOf: [
        new OA\Schema(properties: [new OA\Property(property: 'id', type: 'integer', example: 1)]),
        new OA\Schema(ref: '#/components/schemas/SettingInput')
    ]
)]
#[OA\Schema(
    schema: 'FlightVerdict',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['good', 'caution', 'not_recommended'], example: 'not_recommended'),
        new OA\Property(property: 'can_fly', type: 'boolean', example: false),
        new OA\Property(property: 'reasons', type: 'array', items: new OA\Items(type: 'string'), example: ['Rain expected.', 'Wind gusts 48 km/h are above the 43 km/h limit.']),
        new OA\Property(property: 'blocking', type: 'array', items: new OA\Items(type: 'string'))
    ]
)]
#[OA\Schema(
    schema: 'Location',
    properties: [
        new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Sarajevo'),
        new OA\Property(property: 'country', type: 'string', nullable: true, example: 'Bosnia and Herzegovina'),
        new OA\Property(property: 'latitude', type: 'number', example: 43.84864),
        new OA\Property(property: 'longitude', type: 'number', example: 18.35644),
        new OA\Property(property: 'timezone', type: 'string', example: 'Europe/Sarajevo')
    ]
)]
#[OA\Schema(
    schema: 'FlightConditions',
    properties: [
        new OA\Property(property: 'location', ref: '#/components/schemas/Location'),
        new OA\Property(property: 'weather', type: 'object', example: ['time' => '2026-11-21T10:00', 'temperature' => 6.2, 'wind_speed' => 11.2, 'wind_gusts' => 23.0, 'precipitation' => 0, 'cloud_cover' => 40, 'visibility' => 24000, 'weather_code' => 2, 'description' => 'Partly cloudy']),
        new OA\Property(property: 'units', type: 'object'),
        new OA\Property(property: 'flight', ref: '#/components/schemas/FlightVerdict'),
        new OA\Property(property: 'source', type: 'string', example: 'Open-Meteo.com')
    ]
)]
#[OA\Schema(
    schema: 'ForecastDay',
    properties: [
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-11-22'),
        new OA\Property(property: 'weather_code', type: 'integer', example: 61),
        new OA\Property(property: 'description', type: 'string', example: 'Rain'),
        new OA\Property(property: 'temperature_min', type: 'number', example: 2.1),
        new OA\Property(property: 'temperature_max', type: 'number', example: 9.4),
        new OA\Property(property: 'precipitation', type: 'number', example: 4.2),
        new OA\Property(property: 'precipitation_probability', type: 'integer', example: 85),
        new OA\Property(property: 'snowfall', type: 'number', example: 0),
        new OA\Property(property: 'wind_speed', type: 'number', description: 'Maximum wind speed in km/h', example: 19.1),
        new OA\Property(property: 'wind_gusts', type: 'number', description: 'Maximum wind gusts in km/h', example: 42.5),
        new OA\Property(property: 'flight', ref: '#/components/schemas/FlightVerdict')
    ]
)]
#[OA\Schema(
    schema: 'Forecast',
    properties: [
        new OA\Property(property: 'location', ref: '#/components/schemas/Location'),
        new OA\Property(property: 'days', type: 'array', items: new OA\Items(ref: '#/components/schemas/ForecastDay')),
        new OA\Property(property: 'units', type: 'object'),
        new OA\Property(property: 'source', type: 'string', example: 'Open-Meteo.com')
    ]
)]
class OpenApiSpec
{
}
