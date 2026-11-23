<?php

/*
 * Role routes. Each route only reads the request, calls role_service and returns JSON.
 * Validation and business rules live in services/RoleService.php.
 */

Flight::route('GET /roles', function () {
    Flight::json(Flight::role_service()->getAll());
});

Flight::route('GET /roles/@id:[0-9]+', function ($id) {
    Flight::json(Flight::role_service()->getById((int) $id));
});

Flight::route('POST /roles', function () {
    $id = Flight::role_service()->add(getRequestBody());
    Flight::json(Flight::role_service()->getById($id), 201);
});

Flight::route('PUT /roles/@id:[0-9]+', function ($id) {
    Flight::role_service()->update(getRequestBody(), (int) $id);
    Flight::json(Flight::role_service()->getById((int) $id));
});

Flight::route('DELETE /roles/@id:[0-9]+', function ($id) {
    Flight::role_service()->delete((int) $id);
    Flight::json(['message' => 'Role deleted.']);
});
