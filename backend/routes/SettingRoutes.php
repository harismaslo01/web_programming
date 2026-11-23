<?php

/*
 * Setting routes. Each route only reads the request, calls setting_service and returns JSON.
 * Validation and business rules live in services/SettingService.php.
 */

Flight::route('GET /settings', function () {
    Flight::json(Flight::setting_service()->getAll());
});

// Read one setting by its key, e.g. GET /settings/key/default_currency
Flight::route('GET /settings/key/@key:[a-z][a-z0-9_]*', function ($key) {
    Flight::json(Flight::setting_service()->getByKey($key));
});

Flight::route('GET /settings/@id:[0-9]+', function ($id) {
    Flight::json(Flight::setting_service()->getById((int) $id));
});

Flight::route('POST /settings', function () {
    $id = Flight::setting_service()->add(getRequestBody());
    Flight::json(Flight::setting_service()->getById($id), 201);
});

Flight::route('PUT /settings/@id:[0-9]+', function ($id) {
    Flight::setting_service()->update(getRequestBody(), (int) $id);
    Flight::json(Flight::setting_service()->getById((int) $id));
});

Flight::route('DELETE /settings/@id:[0-9]+', function ($id) {
    Flight::setting_service()->delete((int) $id);
    Flight::json(['message' => 'Setting deleted.']);
});
