<?php

/*
 * Order routes. Each route only reads the request, calls order_service and returns JSON.
 * Validation and business rules live in services/OrderService.php.
 */

// GET /orders?user_id=2 returns the orders of one user.
Flight::route('GET /orders', function () {
    $userId = Flight::request()->query['user_id'] ?? null;

    if ($userId !== null) {
        $userId = filter_var($userId, FILTER_VALIDATE_INT);
        if ($userId === false || $userId < 1) {
            throw new HttpException(400, 'The user_id parameter must be a positive whole number.');
        }
        Flight::json(Flight::order_service()->getByUserId($userId));
        return;
    }

    Flight::json(Flight::order_service()->getAll());
});

Flight::route('GET /orders/@id:[0-9]+', function ($id) {
    Flight::json(Flight::order_service()->getById((int) $id));
});

Flight::route('POST /orders', function () {
    $id = Flight::order_service()->add(getRequestBody());
    Flight::json(Flight::order_service()->getById($id), 201);
});

Flight::route('PUT /orders/@id:[0-9]+', function ($id) {
    Flight::order_service()->update(getRequestBody(), (int) $id);
    Flight::json(Flight::order_service()->getById((int) $id));
});

Flight::route('DELETE /orders/@id:[0-9]+', function ($id) {
    Flight::order_service()->delete((int) $id);
    Flight::json(['message' => 'Order deleted.']);
});

// All media files of one order.
Flight::route('GET /orders/@id:[0-9]+/media', function ($id) {
    Flight::json(Flight::media_service()->getByOrderId((int) $id));
});
