<?php

require __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/config/AppConfig.php';
require_once __DIR__ . '/config/Logger.php';
require_once __DIR__ . '/middleware/HttpException.php';

/*
 * Scheduled dates in the database are local time (Bosnia and Herzegovina).
 * PHP defaults to UTC, which would shift "is this date in the past?" checks and log times by 1-2 hours.
 */
try {
    date_default_timezone_set(AppConfig::get('timezone', 'Europe/Sarajevo'));
} catch (RuntimeException $exception) {
    // Missing config.php: keep a sane default; the first database call then returns a logged 500.
    date_default_timezone_set('Europe/Sarajevo');
}

/*
 * Global error handler.
 * - HttpException (400, 404, 409, 503 ...): expected error, its message is returned.
 * - Anything else (PDOException, TypeError ...): written to logs/error.log and the client
 *   receives a generic 500 message without stack traces, SQL or file paths.
 */
Flight::map('error', function (Throwable $exception) {
    if ($exception instanceof HttpException) {
        $status = $exception->getStatusCode();
        $message = $exception->getMessage();
    } else {
        $status = 500;
        $message = 'Something went wrong on the server. Please try again later.';
        Logger::error(sprintf(
            '%s %s 500 %s: %s (%s:%d)',
            Flight::request()->method,
            Flight::request()->url,
            get_class($exception),
            $exception->getMessage(),
            basename($exception->getFile()),
            $exception->getLine()
        ));
    }

    Flight::response()
        ->clearBody()
        ->status($status)
        ->header('Content-Type', 'application/json; charset=utf-8')
        ->write(json_encode(['error' => $message], JSON_INVALID_UTF8_SUBSTITUTE))
        ->send();
});

Flight::map('notFound', function () {
    Flight::response()
        ->clearBody()
        ->status(404)
        ->header('Content-Type', 'application/json; charset=utf-8')
        ->write(json_encode(['error' => 'Endpoint not found.']))
        ->send();
});

Flight::map('methodNotFound', function ($route) {
    Flight::response()
        ->clearBody()
        ->status(405)
        ->header('Allow', implode(', ', $route->methods))
        ->header('Content-Type', 'application/json; charset=utf-8')
        ->write(json_encode(['error' => 'Method not allowed.']))
        ->send();
});

// Services (business logic). Routes reach them through Flight::<name>().
require_once __DIR__ . '/services/HealthService.php';
require_once __DIR__ . '/services/RoleService.php';
require_once __DIR__ . '/services/UserService.php';
require_once __DIR__ . '/services/ServiceService.php';
require_once __DIR__ . '/services/DroneService.php';
require_once __DIR__ . '/services/OrderService.php';
require_once __DIR__ . '/services/MediaService.php';
require_once __DIR__ . '/services/SettingService.php';
require_once __DIR__ . '/services/WeatherService.php';

Flight::register('health_service', 'HealthService');
Flight::register('role_service', 'RoleService');
Flight::register('user_service', 'UserService');
Flight::register('service_service', 'ServiceService');
Flight::register('drone_service', 'DroneService');
Flight::register('order_service', 'OrderService');
Flight::register('media_service', 'MediaService');
Flight::register('setting_service', 'SettingService');
Flight::register('weather_service', 'WeatherService');

// Routes (presentation layer).
require_once __DIR__ . '/routes/RouteHelpers.php';
require_once __DIR__ . '/routes/HealthRoutes.php';
require_once __DIR__ . '/routes/RoleRoutes.php';
require_once __DIR__ . '/routes/UserRoutes.php';
require_once __DIR__ . '/routes/ServiceRoutes.php';
require_once __DIR__ . '/routes/DroneRoutes.php';
require_once __DIR__ . '/routes/OrderRoutes.php';
require_once __DIR__ . '/routes/MediaRoutes.php';
require_once __DIR__ . '/routes/SettingRoutes.php';
require_once __DIR__ . '/routes/WeatherRoutes.php';

Flight::start();
