# PHP Backend (Milestone 3)

REST API built with FlightPHP, PDO/MySQL, and swagger-php. Milestone 3 has no
authentication yet: every endpoint is public. JWT and roles come in Milestone 4.

## Requirements

- PHP 8.4 or newer with the `pdo_mysql`, `curl`, `openssl`, and `mbstring`
  extensions (swagger-php 6 and the Symfony 8 packages require PHP 8.4)
- MySQL or MariaDB (for example from XAMPP)
- Composer

## Setup

1. Install the dependencies:

   ```bash
   cd backend
   composer install
   cd ..
   ```

2. Create the local configuration (ignored by Git):

   ```bash
   cp backend/config/config.example.php backend/config/config.php
   ```

   Then edit `backend/config/config.php`:

   | Key | Meaning |
   | --- | --- |
   | `host`, `port`, `database`, `username`, `password`, `charset` | MySQL connection |
   | `timezone` | Time zone of scheduled dates and logs (`Europe/Sarajevo`) |
   | `weather_api_url`, `geocoding_api_url` | Open-Meteo endpoints |
   | `weather_api_key` | Empty; Open-Meteo needs no key for non-commercial use |
   | `weather_api_timeout` | Timeout of each external call in seconds (5) |
   | `weather_check_days` | Bookings within this many days are checked against the forecast (7) |
   | `ca_bundle_path` | Optional CA certificate file for HTTPS. On XAMPP/Windows use `C:/xampp/apache/bin/curl-ca-bundle.crt` if weather calls fail with "unable to get local issuer certificate". Leave empty on Linux. |

3. Import the database:

   ```bash
   mysql -u root -p < database/dronepilot_services.sql
   ```

   A database imported before Milestone 3 only needs the new `orders.location`
   column:

   ```bash
   mysql -u root -p < database/migrations/m3_add_order_location.sql
   ```

4. Start the server, either XAMPP Apache or PHP's built-in server:

   - **Apache (XAMPP):** `backend/.htaccess` sends every request that is not a
     real file to `index.php` and blocks HTTP access to `config/`, `logs/`,
     `vendor/`, and the source folders.
     - API: `http://localhost/web_programming/backend/`
     - Swagger UI: `http://localhost/web_programming/backend/public/v1/docs/`
   - **Built-in server:** run `php -S localhost:8000 server.php` from the
     repository root.
     - API: `http://localhost:8000/backend/`
     - Swagger UI: `http://localhost:8000/backend/public/v1/docs/`

## Architecture

```text
request -> server.php -> backend/index.php (FlightPHP)
        -> routes/*Routes.php   reads the request, returns JSON
        -> services/*Service.php validation and business rules
        -> dao/*Dao.php         prepared SQL statements (PDO)
        -> MySQL
```

- Routes never call DAOs; only services do.
- `BaseService` contains shared CRUD, the 404 check, and validation helpers.
- Errors are `HttpException` objects with a status code. The global handler in
  `index.php` returns `{"error": "..."}`. Unexpected errors return a generic 500
  and are written to `backend/logs/error.log`.
- `Logger::info()` writes to `backend/logs/info.log` (successful external API
  calls), and `Logger::error()` writes to `backend/logs/error.log`.

## Endpoints

All paths start with `/backend`. Every entity supports:

| Method | Path | Success |
| --- | --- | --- |
| GET | `/{entity}` | 200 |
| GET | `/{entity}/{id}` | 200, or 404 |
| POST | `/{entity}` | 201 with the created record |
| PUT | `/{entity}/{id}` | 200 with the updated record (send only the changed fields) |
| DELETE | `/{entity}/{id}` | 200 `{"message": ...}` |

Entities: `roles`, `users`, `services`, `drones`, `orders`, `media`, `settings`.

Extra endpoints and filters:

- `GET /` - health check
- `GET /users/{id}/orders` - orders of one user
- `GET /services?active=true` - only bookable services
- `GET /drones?search=air`, `GET /drones?status=available&category=Compact&brand=DJI`
- `GET /orders?user_id=2`, `GET /orders/{id}/media`
- `GET /media?order_id=1`
- `GET /settings/key/{key}` - for example `/settings/key/site_name`
- `GET /weather/flight-conditions?city=Sarajevo` or
  `?latitude=43.85&longitude=18.41` - current weather and a flight verdict
- `GET /weather/forecast?city=Mostar&days=5` - daily forecast (1-16 days) with a
  verdict per day

Status codes:

| Code | When |
| --- | --- |
| 400 | Invalid JSON, unknown field, missing required field, wrong type or format, bad weather for a booking |
| 404 | Record, user, location, or endpoint not found |
| 405 | Method not supported on that path |
| 409 | Duplicate unique value (email, serial number, order number, setting key, role name), record still in use, or a completed/cancelled order changed |
| 500 | Unexpected server error (details only in `error.log`) |
| 503 | Open-Meteo is unavailable or returned invalid data (weather endpoints only) |

## External API: Open-Meteo

`services/WeatherService.php` calls the Open-Meteo geocoding and forecast APIs
(https://open-meteo.com). Every call has a timeout. Network errors, non-200
responses, and invalid JSON are written to `error.log` and become a 503 with a
friendly message. Successful calls are written to `info.log`.

**Booking weather check.** `POST /orders` requires `user_id`, `service_id`,
`title`, `location`, and `scheduled_date`. If the scheduled date is less than
7 days away, `OrderService` checks the hourly forecast for that location from
the booked hour for 3 hours. The order is refused with 400
`"Drones do not fly in this weather..."` when it finds thunderstorm, snow, rain,
drizzle, fog, any precipitation or a rain probability of 60% or more, wind above 38 km/h, gusts above 43 km/h, visibility below 1 km, or
temperatures outside -10 to 40 °C. Changing `location` or `scheduled_date`
with PUT repeats the check. If Open-Meteo is down, the order is accepted and the
failure is logged, so bookings keep working.

## Tests

```bash
php backend/test/DaoTest.php
```

The script checks sample reads and creates, updates, and deletes a temporary
setting record. The API endpoints were tested manually with curl and Postman.
