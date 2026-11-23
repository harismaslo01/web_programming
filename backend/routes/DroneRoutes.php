<?php

/*
 * Drone routes. GET /drones supports ?search=... and ?status=&category=&brand= filters.
 * Validation and business rules live in services/DroneService.php.
 */

Flight::route('GET /drones', function () {
    $query = Flight::request()->query;

    if (isset($query['search'])) {
        $search = $query['search'];
        if (!is_string($search)) {
            throw new HttpException(400, 'The search parameter must be a string.');
        }

        if (trim($search) !== '') {
            Flight::json(Flight::drone_service()->search(trim($search)));
            return;
        }
    }

    $filters = array_intersect_key(
        $query->getData(),
        array_flip(['status', 'category', 'brand'])
    );

    if ($filters !== []) {
        Flight::json(Flight::drone_service()->filter($filters));
        return;
    }

    Flight::json(Flight::drone_service()->getAll());
});

Flight::route('GET /drones/@id:[0-9]+', function ($id) {
    $drone = Flight::drone_service()->getById((int) $id);
    Flight::json($drone);
});

Flight::route('POST /drones', function () {
    $id = Flight::drone_service()->add(getRequestBody());
    Flight::json(Flight::drone_service()->getById($id), 201);
});

Flight::route('PUT /drones/@id:[0-9]+', function ($id) {
    Flight::drone_service()->update(getRequestBody(), (int) $id);
    Flight::json(Flight::drone_service()->getById((int) $id));
});

Flight::route('DELETE /drones/@id:[0-9]+', function ($id) {
    Flight::drone_service()->delete((int) $id);
    Flight::json(['message' => 'Drone deleted.']);
});
