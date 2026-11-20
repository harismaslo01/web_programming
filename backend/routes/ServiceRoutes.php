<?php

/*
 * Aerial service routes. Each route only reads the request, calls service_service and returns JSON.
 * Validation and business rules live in services/ServiceService.php.
 */

// GET /services?active=true returns only services that can be booked.
Flight::route('GET /services', function () {
    $active = Flight::request()->query['active'] ?? null;

    if ($active !== null) {
        $active = filter_var($active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($active === null) {
            throw new HttpException(400, 'The active parameter must be true or false.');
        }
        // active=false means "no filter": all services, active and inactive.
        if ($active) {
            Flight::json(Flight::service_service()->getActive());
            return;
        }
    }

    Flight::json(Flight::service_service()->getAll());
});

Flight::route('GET /services/@id:[0-9]+', function ($id) {
    Flight::json(Flight::service_service()->getById((int) $id));
});

Flight::route('POST /services', function () {
    $id = Flight::service_service()->add(getRequestBody());
    Flight::json(Flight::service_service()->getById($id), 201);
});

Flight::route('PUT /services/@id:[0-9]+', function ($id) {
    Flight::service_service()->update(getRequestBody(), (int) $id);
    Flight::json(Flight::service_service()->getById((int) $id));
});

Flight::route('DELETE /services/@id:[0-9]+', function ($id) {
    Flight::service_service()->delete((int) $id);
    Flight::json(['message' => 'Service deleted.']);
});
