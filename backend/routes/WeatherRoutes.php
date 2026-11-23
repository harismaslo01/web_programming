<?php

/*
 * External API endpoints (Open-Meteo). Logic lives in services/WeatherService.php.
 *
 *   GET /weather/flight-conditions?city=Sarajevo                 current conditions + verdict
 *   GET /weather/flight-conditions?latitude=43.85&longitude=18.41
 *   GET /weather/forecast?city=Sarajevo&days=5                   daily forecast + verdict per day
 *
 * The same forecast is used by OrderService to refuse bookings in bad weather.
 */
Flight::route('GET /weather/flight-conditions', function () {
    $query = Flight::request()->query;

    if (isset($query['city'])) {
        if (!is_string($query['city'])) {
            throw new HttpException(400, 'The city parameter must be text.');
        }
        Flight::json(Flight::weather_service()->getFlightConditionsForCity($query['city']));
        return;
    }

    if (isset($query['latitude'], $query['longitude'])) {
        Flight::json(Flight::weather_service()->getFlightConditionsForCoordinates(
            $query['latitude'],
            $query['longitude']
        ));
        return;
    }

    throw new HttpException(400, 'Send either a city or latitude and longitude.');
});

Flight::route('GET /weather/forecast', function () {
    $query = Flight::request()->query;

    if (!isset($query['city']) || !is_string($query['city'])) {
        throw new HttpException(400, 'The city parameter is required.');
    }

    Flight::json(Flight::weather_service()->getForecastForCity($query['city'], $query['days'] ?? 5));
});
