<?php

/*
 * User routes. Each route only reads the request, calls user_service and returns JSON.
 * Validation and business rules live in services/UserService.php.
 */

Flight::route('GET /users', function () {
    Flight::json(Flight::user_service()->getAll());
});

Flight::route('GET /users/@id:[0-9]+', function ($id) {
    Flight::json(Flight::user_service()->getById((int) $id));
});

Flight::route('POST /users', function () {
    $id = Flight::user_service()->add(getRequestBody());
    Flight::json(Flight::user_service()->getById($id), 201);
});

Flight::route('PUT /users/@id:[0-9]+', function ($id) {
    Flight::user_service()->update(getRequestBody(), (int) $id);
    Flight::json(Flight::user_service()->getById((int) $id));
});

Flight::route('DELETE /users/@id:[0-9]+', function ($id) {
    Flight::user_service()->delete((int) $id);
    Flight::json(['message' => 'User deleted.']);
});

// All orders of one user.
Flight::route('GET /users/@id:[0-9]+/orders', function ($id) {
    Flight::json(Flight::order_service()->getByUserId((int) $id));
});
