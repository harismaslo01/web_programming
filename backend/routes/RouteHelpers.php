<?php

/*
 * Returns the JSON request body as an array.
 * FlightPHP decodes application/json bodies into Flight::request()->data.
 * An empty or invalid body becomes [] and the service then answers with 400.
 */
function getRequestBody(): array
{
    $request = Flight::request();

    if ($request->getBody() !== '' && json_decode($request->getBody()) === null) {
        throw new HttpException(400, 'The request body must be valid JSON.');
    }

    $data = $request->data->getData();
    return is_array($data) ? $data : [];
}
