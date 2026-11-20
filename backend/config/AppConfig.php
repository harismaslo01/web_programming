<?php

/*
 * Reads backend/config/config.php once and returns single values from it.
 * Used by Database (credentials) and WeatherService (API URL, key, timeout).
 */
class AppConfig
{
    private static ?array $values = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$values === null) {
            $file = __DIR__ . '/config.php';
            if (!is_file($file)) {
                throw new RuntimeException(
                    'Missing backend/config/config.php. Copy config.example.php and fill in your values.'
                );
            }
            self::$values = require $file;
        }

        return self::$values[$key] ?? $default;
    }
}
