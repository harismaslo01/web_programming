<?php

require_once __DIR__ . '/../config/AppConfig.php';
require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../middleware/HttpException.php';

/*
 * External API integration: Open-Meteo (https://open-meteo.com).
 *
 * Features:
 *   1. Current flight conditions for a city or coordinates      (GET /weather/flight-conditions)
 *   2. Daily flight forecast for the next few days              (GET /weather/forecast)
 *   3. Booking check: OrderService calls checkBookingWeather() and refuses an order
 *      when rain, snow, fog, a storm or strong wind is forecast for the booked time.
 *
 * Failure handling (project requirement):
 *   - every call has a timeout (weather_api_timeout in config.php),
 *   - network errors, non-200 responses and invalid JSON are caught,
 *   - failures are written to backend/logs/error.log, successful calls to info.log,
 *   - the client receives a friendly 503 message and the rest of the API keeps working.
 */
class WeatherService
{
    // Limits for small/consumer drones (e.g. DJI Lito, Air, Mavic). Wind values are in km/h (1 m/s = 3.6 km/h).
    private const WIND_CAUTION = 29.0;   // about 8 m/s
    private const WIND_LIMIT = 38.0;     // about 10.7 m/s
    private const GUST_CAUTION = 36.0;   // 10 m/s
    private const GUST_LIMIT = 43.0;     // about 12 m/s
    private const RAIN_PROBABILITY_LIMIT = 60;  // %
    private const VISIBILITY_CAUTION = 3000;    // meters
    private const VISIBILITY_LIMIT = 1000;      // meters, visual line of sight
    private const TEMPERATURE_MIN = -10.0;      // °C, batteries lose capacity quickly below this
    private const TEMPERATURE_MAX = 40.0;       // °C
    private const FLIGHT_WINDOW_HOURS = 3;      // a booked job is checked from its start hour for 3 hours
    private const MAX_FORECAST_DAYS = 16;       // Open-Meteo limit

    private string $forecastUrl;
    private string $geocodingUrl;
    private string $apiKey;
    private int $timeout;
    private int $bookingCheckDays;
    private string $caBundlePath;

    public function __construct()
    {
        // Optional CA certificate file for HTTPS (needed on Windows PHP builds without one).
        $this->caBundlePath = (string) AppConfig::get('ca_bundle_path', '');
        $this->forecastUrl = AppConfig::get('weather_api_url', 'https://api.open-meteo.com/v1/forecast');
        $this->geocodingUrl = AppConfig::get('geocoding_api_url', 'https://geocoding-api.open-meteo.com/v1/search');
        $this->apiKey = (string) AppConfig::get('weather_api_key', '');
        $this->timeout = (int) AppConfig::get('weather_api_timeout', 5);
        $this->bookingCheckDays = (int) AppConfig::get('weather_check_days', 7);
    }

    /* ---------- 1. Current conditions ---------- */

    public function getFlightConditionsForCity(string $city): array
    {
        return $this->getCurrentConditions($this->findLocation($city));
    }

    public function getFlightConditionsForCoordinates(mixed $latitude, mixed $longitude): array
    {
        return $this->getCurrentConditions($this->coordinatesLocation($latitude, $longitude));
    }

    private function getCurrentConditions(array $location): array
    {
        $response = $this->requestJson($this->forecastUrl, [
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'current' => 'temperature_2m,precipitation,weather_code,cloud_cover,wind_speed_10m,wind_gusts_10m,visibility',
            'wind_speed_unit' => 'kmh',
            'timezone' => 'auto'
        ]);

        if (!isset($response['current']) || !is_array($response['current'])) {
            Logger::error('Open-Meteo returned a response without current weather data.');
            throw new HttpException(503, 'Weather data is currently unavailable. Please try again later.');
        }

        $current = $response['current'];
        $weather = [
            'time' => $current['time'] ?? null,
            'temperature' => $current['temperature_2m'] ?? null,
            'wind_speed' => $current['wind_speed_10m'] ?? null,
            'wind_gusts' => $current['wind_gusts_10m'] ?? null,
            'precipitation' => $current['precipitation'] ?? null,
            'cloud_cover' => $current['cloud_cover'] ?? null,
            'visibility' => $current['visibility'] ?? null,
            'weather_code' => $current['weather_code'] ?? null,
            'description' => $this->describeWeatherCode($current['weather_code'] ?? null)
        ];

        return [
            'location' => $location + ['timezone' => $response['timezone'] ?? null],
            'weather' => $weather,
            'units' => ['temperature' => '°C', 'wind_speed' => 'km/h', 'wind_gusts' => 'km/h',
                'precipitation' => 'mm', 'cloud_cover' => '%', 'visibility' => 'm'],
            'flight' => $this->evaluateFlight($weather),
            'source' => 'Open-Meteo.com'
        ];
    }

    /* ---------- 2. Daily forecast for the next days ---------- */

    public function getForecastForCity(string $city, mixed $days = 5): array
    {
        $days = filter_var($days, FILTER_VALIDATE_INT);
        if ($days === false || $days < 1 || $days > self::MAX_FORECAST_DAYS) {
            throw new HttpException(400, sprintf('The days parameter must be between 1 and %d.', self::MAX_FORECAST_DAYS));
        }

        $location = $this->findLocation($city);
        $response = $this->requestJson($this->forecastUrl, [
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,'
                . 'precipitation_probability_max,snowfall_sum,wind_speed_10m_max,wind_gusts_10m_max',
            'wind_speed_unit' => 'kmh',
            'timezone' => 'auto',
            'forecast_days' => $days
        ]);

        $daily = $response['daily'] ?? null;
        if (!is_array($daily) || !isset($daily['time']) || !is_array($daily['time'])) {
            Logger::error('Open-Meteo returned a response without daily forecast data.');
            throw new HttpException(503, 'Weather forecast is currently unavailable. Please try again later.');
        }

        $forecast = [];
        foreach ($daily['time'] as $index => $date) {
            $weather = [
                'date' => $date,
                'weather_code' => $daily['weather_code'][$index] ?? null,
                'description' => $this->describeWeatherCode($daily['weather_code'][$index] ?? null),
                'temperature_min' => $daily['temperature_2m_min'][$index] ?? null,
                'temperature_max' => $daily['temperature_2m_max'][$index] ?? null,
                'precipitation' => $daily['precipitation_sum'][$index] ?? null,
                'precipitation_probability' => $daily['precipitation_probability_max'][$index] ?? null,
                'snowfall' => $daily['snowfall_sum'][$index] ?? null,
                'wind_speed' => $daily['wind_speed_10m_max'][$index] ?? null,
                'wind_gusts' => $daily['wind_gusts_10m_max'][$index] ?? null
            ];
            $weather['flight'] = $this->evaluateFlight($weather);
            $forecast[] = $weather;
        }

        return [
            'location' => $location + ['timezone' => $response['timezone'] ?? null],
            'days' => $forecast,
            'units' => ['temperature' => '°C', 'precipitation' => 'mm', 'snowfall' => 'cm',
                'wind_speed' => 'km/h', 'wind_gusts' => 'km/h', 'precipitation_probability' => '%'],
            'source' => 'Open-Meteo.com'
        ];
    }

    /* ---------- 3. Booking check (used by OrderService) ---------- */

    /*
     * Checks the hourly forecast for a booked job: from the scheduled hour for FLIGHT_WINDOW_HOURS hours.
     * Returns:
     *   ['checked' => false, ...]  - date is further away than weather_check_days (no reliable forecast yet)
     *   ['checked' => true, 'can_fly' => bool, 'reasons' => [...], ...]
     * Throws HttpException 400 (unknown location) or 503 (weather service unavailable).
     */
    public function checkBookingWeather(string $locationName, string $scheduledDate): array
    {
        $start = new DateTime($scheduledDate);
        $daysAhead = (int) floor(($start->getTimestamp() - time()) / 86400);

        if ($daysAhead >= $this->bookingCheckDays) {
            return [
                'checked' => false,
                'can_fly' => true,
                'reasons' => [],
                'message' => sprintf('No forecast yet: the weather is checked only %d days ahead.', $this->bookingCheckDays)
            ];
        }

        try {
            $location = $this->findLocation($locationName);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 404) {
                throw new HttpException(400, sprintf('The location "%s" was not found. Enter a city or place name.', $locationName));
            }
            throw $exception;
        }

        $end = (clone $start)->modify('+' . (self::FLIGHT_WINDOW_HOURS - 1) . ' hours');
        $response = $this->requestJson($this->forecastUrl, [
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'hourly' => 'temperature_2m,precipitation,precipitation_probability,snowfall,weather_code,'
                . 'wind_speed_10m,wind_gusts_10m,visibility',
            'wind_speed_unit' => 'kmh',
            'timezone' => 'auto',
            'start_hour' => $start->format('Y-m-d\TH:00'),
            'end_hour' => $end->format('Y-m-d\TH:00')
        ]);

        $hourly = $response['hourly'] ?? null;
        if (!is_array($hourly) || empty($hourly['time'])) {
            Logger::error('Open-Meteo returned a response without hourly forecast data.');
            throw new HttpException(503, 'Weather forecast is currently unavailable. Please try again later.');
        }

        // reason text => hours in which it occurs, e.g. "Rain expected." => ["15:00", "16:00"]
        $hoursByReason = [];
        foreach ($hourly['time'] as $index => $time) {
            $weather = [
                'temperature' => $hourly['temperature_2m'][$index] ?? null,
                'precipitation' => $hourly['precipitation'][$index] ?? null,
                'precipitation_probability' => $hourly['precipitation_probability'][$index] ?? null,
                'snowfall' => $hourly['snowfall'][$index] ?? null,
                'weather_code' => $hourly['weather_code'][$index] ?? null,
                'wind_speed' => $hourly['wind_speed_10m'][$index] ?? null,
                'wind_gusts' => $hourly['wind_gusts_10m'][$index] ?? null,
                'visibility' => $hourly['visibility'][$index] ?? null
            ];

            foreach ($this->evaluateFlight($weather)['blocking'] as $reason) {
                // Time from the API is local, e.g. 2026-11-23T10:00 -> "10:00"
                $hoursByReason[$reason][] = substr($time, 11, 5);
            }
        }

        // "Rain expected. (15:00, 16:00, 17:00)" instead of repeating the same sentence every hour.
        $reasons = [];
        foreach ($hoursByReason as $reason => $hours) {
            $reasons[] = sprintf('%s (%s)', $reason, implode(', ', $hours));
        }

        return [
            'checked' => true,
            'can_fly' => $reasons === [],
            'reasons' => $reasons,
            'location' => $location
        ];
    }

    /* ---------- Business logic: weather -> flight verdict ---------- */

    /*
     * Works with current, hourly and daily values. Missing values are simply skipped.
     * "blocking" reasons mean the drone does not fly; "warnings" mean fly carefully.
     */
    private function evaluateFlight(array $weather): array
    {
        $blocking = [];
        $warnings = [];

        $code = $weather['weather_code'] ?? null;
        $wind = $weather['wind_speed'] ?? null;
        $gusts = $weather['wind_gusts'] ?? null;
        $rain = $weather['precipitation'] ?? null;
        $rainProbability = $weather['precipitation_probability'] ?? null;
        $snow = $weather['snowfall'] ?? null;
        $visibility = $weather['visibility'] ?? null;
        $temperatures = array_filter([
            $weather['temperature'] ?? null,
            $weather['temperature_min'] ?? null,
            $weather['temperature_max'] ?? null
        ], fn ($value) => $value !== null);

        // Weather type: thunderstorm, snow, rain, drizzle or fog.
        if ($code !== null) {
            $code = (int) $code;
            if ($code >= 95) {
                $blocking[] = 'Thunderstorm expected.';
            } elseif (($code >= 71 && $code <= 77) || $code === 85 || $code === 86) {
                $blocking[] = 'Snow expected.';
            } elseif (($code >= 61 && $code <= 67) || ($code >= 80 && $code <= 82)) {
                $blocking[] = 'Rain expected.';
            } elseif ($code >= 51 && $code <= 57) {
                $blocking[] = 'Drizzle expected.';
            } elseif ($code === 45 || $code === 48) {
                $blocking[] = 'Fog expected.';
            }
        }

        if ($snow !== null && $snow > 0 && !in_array('Snow expected.', $blocking, true)) {
            $blocking[] = sprintf('Snowfall %.1f cm.', $snow);
        }

        // Precipitation is only reported separately when the weather type above did not already block.
        $weatherTypeBlocks = $blocking !== [];
        if ($rain !== null && $rain > 0) {
            if (!$weatherTypeBlocks) {
                $blocking[] = sprintf('Precipitation %.1f mm. Most drones are not waterproof.', $rain);
            }
        } elseif ($rainProbability !== null && $rainProbability >= self::RAIN_PROBABILITY_LIMIT) {
            $blocking[] = sprintf('Rain probability %d%%.', (int) $rainProbability);
        }

        if ($wind !== null) {
            if ($wind > self::WIND_LIMIT) {
                $blocking[] = sprintf('Wind %.0f km/h is above the %.0f km/h limit.', $wind, self::WIND_LIMIT);
            } elseif ($wind > self::WIND_CAUTION) {
                $warnings[] = sprintf('Strong wind (%.0f km/h). Fly carefully and keep the drone close.', $wind);
            }
        }

        if ($gusts !== null) {
            if ($gusts > self::GUST_LIMIT) {
                $blocking[] = sprintf('Wind gusts %.0f km/h are above the %.0f km/h limit.', $gusts, self::GUST_LIMIT);
            } elseif ($gusts > self::GUST_CAUTION) {
                $warnings[] = sprintf('Wind gusts up to %.0f km/h.', $gusts);
            }
        }

        if ($visibility !== null) {
            if ($visibility < self::VISIBILITY_LIMIT) {
                $blocking[] = sprintf('Visibility %d m is too low for visual line of sight.', (int) $visibility);
            } elseif ($visibility < self::VISIBILITY_CAUTION) {
                $warnings[] = sprintf('Reduced visibility (%d m).', (int) $visibility);
            }
        }

        foreach ($temperatures as $temperature) {
            if ($temperature < self::TEMPERATURE_MIN || $temperature > self::TEMPERATURE_MAX) {
                $blocking[] = sprintf('Temperature %.1f °C is outside the safe battery range.', $temperature);
                break;
            }
            if ($temperature < 0) {
                $warnings[] = 'Below 0 °C: keep batteries warm, flight time will be shorter.';
                break;
            }
        }

        $blocking = array_values(array_unique($blocking));
        $warnings = array_values(array_unique($warnings));

        return [
            'status' => $blocking !== [] ? 'not_recommended' : ($warnings !== [] ? 'caution' : 'good'),
            'can_fly' => $blocking === [],
            'reasons' => array_merge($blocking, $warnings),
            'blocking' => $blocking
        ];
    }

    /* ---------- Locations ---------- */

    private function findLocation(string $city): array
    {
        $city = trim($city);
        if ($city === '' || mb_strlen($city) > 100 || !preg_match("/^[\\p{L}\\p{M} .,'\\-]+$/u", $city)) {
            throw new HttpException(400, 'Please enter a valid city or place name.');
        }

        // "Sarajevo, Bosnia" -> search only "Sarajevo"; Open-Meteo expects just the place name.
        $searchName = trim(explode(',', $city)[0]);

        $response = $this->requestJson($this->geocodingUrl, [
            'name' => $searchName,
            'count' => 1,
            'language' => 'en',
            'format' => 'json'
        ]);

        if (empty($response['results'][0])) {
            throw new HttpException(404, sprintf('Location "%s" was not found.', $city));
        }

        $place = $response['results'][0];
        return [
            'name' => $place['name'] ?? $searchName,
            'country' => $place['country'] ?? null,
            'latitude' => (float) $place['latitude'],
            'longitude' => (float) $place['longitude']
        ];
    }

    private function coordinatesLocation(mixed $latitude, mixed $longitude): array
    {
        $latitude = filter_var($latitude, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($longitude, FILTER_VALIDATE_FLOAT);

        if ($latitude === false || $latitude < -90 || $latitude > 90
            || $longitude === false || $longitude < -180 || $longitude > 180) {
            throw new HttpException(400, 'Latitude must be between -90 and 90 and longitude between -180 and 180.');
        }

        return ['name' => null, 'country' => null, 'latitude' => $latitude, 'longitude' => $longitude];
    }

    /* ---------- HTTP ---------- */

    /*
     * Calls an external URL and decodes JSON. Converts every failure into a logged 503.
     */
    private function requestJson(string $url, array $query): array
    {
        if ($this->apiKey !== '') {
            $query['apikey'] = $this->apiKey;
        }

        $fullUrl = $url . '?' . http_build_query($query);
        // Never write the API key into the logs.
        $logUrl = $this->apiKey !== '' ? str_replace($this->apiKey, '***', $fullUrl) : $fullUrl;
        $startedAt = microtime(true);

        try {
            [$statusCode, $body] = $this->httpGet($fullUrl);
        } catch (RuntimeException $exception) {
            Logger::error(sprintf('External API failure GET %s: %s', $logUrl, $exception->getMessage()));
            throw new HttpException(503, 'The weather service is not responding right now. Please try again in a few minutes.');
        }

        $duration = (int) round((microtime(true) - $startedAt) * 1000);

        if ($statusCode !== 200) {
            Logger::error(sprintf('External API failure GET %s returned HTTP %d (%dms): %s', $logUrl, $statusCode, $duration, mb_substr((string) $body, 0, 300)));
            throw new HttpException(503, 'The weather service returned an error. Please try again later.');
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            Logger::error(sprintf('External API failure GET %s returned invalid JSON (%dms).', $logUrl, $duration));
            throw new HttpException(503, 'The weather service returned invalid data. Please try again later.');
        }

        Logger::info(sprintf('External API GET %s 200 %dms', $logUrl, $duration));
        return $data;
    }

    /*
     * Returns [statusCode, body]. Uses cURL when available, otherwise PHP streams.
     * Throws RuntimeException on timeouts and network errors.
     * protected so tests can replace it with a fake response.
     */
    protected function httpGet(string $url): array
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $this->timeout,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_USERAGENT => 'DronePilotServices/1.0'
            ]);
            // SSL verification always stays on; only the certificate file location can change.
            if ($this->caBundlePath !== '' && is_file($this->caBundlePath)) {
                curl_setopt($curl, CURLOPT_CAINFO, $this->caBundlePath);
            }

            $body = curl_exec($curl);
            if ($body === false) {
                $error = curl_error($curl);
                curl_close($curl);
                throw new RuntimeException($error !== '' ? $error : 'Unknown cURL error');
            }

            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
            return [$statusCode, $body];
        }

        $options = [
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: DronePilotServices/1.0\r\n"
            ]
        ];
        if ($this->caBundlePath !== '' && is_file($this->caBundlePath)) {
            $options['ssl'] = ['cafile' => $this->caBundlePath];
        }
        $context = stream_context_create($options);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            $error = error_get_last();
            throw new RuntimeException($error['message'] ?? 'Request failed or timed out');
        }

        $statusCode = 0;
        // $http_response_header is filled by file_get_contents for HTTP requests.
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $match)) {
            $statusCode = (int) $match[1];
        }

        return [$statusCode, $body];
    }

    /* WMO weather codes used by Open-Meteo -> short English text. */
    private function describeWeatherCode(mixed $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $code = (int) $code;
        return match (true) {
            $code === 0 => 'Clear sky',
            $code <= 2 => 'Partly cloudy',
            $code === 3 => 'Overcast',
            $code === 45 || $code === 48 => 'Fog',
            $code >= 51 && $code <= 57 => 'Drizzle',
            $code >= 61 && $code <= 67 => 'Rain',
            $code >= 71 && $code <= 77 => 'Snow',
            $code >= 80 && $code <= 82 => 'Rain showers',
            $code === 85 || $code === 86 => 'Snow showers',
            $code >= 95 => 'Thunderstorm',
            default => 'Unknown'
        };
    }
}
