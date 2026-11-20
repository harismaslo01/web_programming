<?php

/*
 * Copy this file to config.php and fill in your local values.
 * config.php is ignored by Git, so real passwords and API keys never reach GitHub.
 */
return [
    // Database (PDO MySQL)
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'dronepilot_services',
    'username' => 'root',
    'password' => 'YOUR_PASSWORD',
    'charset' => 'utf8mb4',

    // Time zone of scheduled dates and log entries
    'timezone' => 'Europe/Sarajevo',

    // External API: Open-Meteo weather (https://open-meteo.com)
    // Open-Meteo does not require a key for non-commercial use; leave the key empty.
    // If you switch to a paid Open-Meteo plan, put the key here and it is sent automatically.
    'weather_api_url' => 'https://api.open-meteo.com/v1/forecast',
    'geocoding_api_url' => 'https://geocoding-api.open-meteo.com/v1/search',
    'weather_api_key' => '',
    'weather_api_timeout' => 5,         // seconds
    'weather_check_days' => 7,          // bookings within this many days are checked against the forecast

    // Optional: CA certificate file for HTTPS calls. Leave empty on Linux servers.
    // On Windows/XAMPP without a configured CA file, use e.g. 'C:/xampp/apache/bin/curl-ca-bundle.crt'.
    'ca_bundle_path' => ''
];
