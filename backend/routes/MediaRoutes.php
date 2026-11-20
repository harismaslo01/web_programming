<?php

/*
 * Media routes. Each route only reads the request, calls media_service and returns JSON.
 * Validation and business rules live in services/MediaService.php.
 */

// GET /media?order_id=1 returns the media of one order.
Flight::route('GET /media', function () {
    $orderId = Flight::request()->query['order_id'] ?? null;

    if ($orderId !== null) {
        $orderId = filter_var($orderId, FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId < 1) {
            throw new HttpException(400, 'The order_id parameter must be a positive whole number.');
        }
        Flight::json(Flight::media_service()->getByOrderId($orderId));
        return;
    }

    Flight::json(Flight::media_service()->getAll());
});

Flight::route('GET /media/@id:[0-9]+', function ($id) {
    Flight::json(Flight::media_service()->getById((int) $id));
});

Flight::route('POST /media', function () {
    $id = Flight::media_service()->add(getRequestBody());
    Flight::json(Flight::media_service()->getById($id), 201);
});

Flight::route('PUT /media/@id:[0-9]+', function ($id) {
    Flight::media_service()->update(getRequestBody(), (int) $id);
    Flight::json(Flight::media_service()->getById((int) $id));
});

Flight::route('DELETE /media/@id:[0-9]+', function ($id) {
    Flight::media_service()->delete((int) $id);
    Flight::json(['message' => 'Media deleted.']);
});
