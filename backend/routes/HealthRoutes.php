<?php

Flight::route('GET /', function () {
    Flight::json(Flight::health_service()->getStatus());
});
