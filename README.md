# DronePilot Services

DronePilot Services is a single-page web application for booking professional
drone work: aerial photography, inspection, and 3D terrain mapping. Customers
choose a service, a place, and a date; administrators manage services, drones,
orders, media, and users.

## Project Status

| Milestone | Content | Status |
| --- | --- | --- |
| M1 | Project structure, static SPA frontend, draft ERD | Completed |
| M2 | MySQL database export, DAO layer | Completed |
| M3 | Services, FlightPHP routes, OpenAPI/Swagger, Open-Meteo external API | Completed (branch `milestone3`) |
| M4 | JWT, roles, middleware, request logging, fully connected frontend | Not started |
| M5 | Frontend MVC, validations, deployment | Not started |

Current state: the REST API and the database work. On the frontend, the
weather widget ("Vrijeme za let" on the home page) and the admin panel
(read-only lists of services, orders, drones, clients, and settings, plus a
dashboard with statistics) load their data from the API. Login is still a mock
stored in `localStorage`; creating, editing, and deleting records from the UI,
JWT login, and the remaining pages are connected in Milestone 4.

## Technology Stack

- Frontend: HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.3, Bootstrap Icons
  (hash-routed SPA)
- Backend: PHP 8.4, FlightPHP, PDO, MySQL/MariaDB
- API documentation: swagger-php (OpenAPI 3) and Swagger UI
- External API: Open-Meteo (weather)

## Database Entities and Relationships

| Table | Purpose |
| --- | --- |
| `roles` | Admin and User roles |
| `users` | Accounts; each user has one role |
| `services` | Bookable aerial services with a price |
| `drones` | Drone fleet with status and battery level |
| `orders` | Bookings: user, service, optional drone, location, scheduled date, status, price |
| `media` | Files delivered for an order |
| `settings` | Application settings (key/value) |

Relationships:

- `users.role_id -> roles.id`
- `orders.user_id -> users.id`
- `orders.service_id -> services.id`
- `orders.drone_id -> drones.id` (optional)
- `media.order_id -> orders.id`

The draft ERD is [database.png](database.png). The importable schema with
sample data is [database/dronepilot_services.sql](database/dronepilot_services.sql).
Milestone 3 added the `orders.location` column; a database imported earlier
needs [database/migrations/m3_add_order_location.sql](database/migrations/m3_add_order_location.sql).

## Roles and Test Accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@dronepilot.ba` | `DronePilot123!` |
| User | `haris@stu.ibu.edu.ba` | `DronePilot123!` |

The sample data also contains five more clients (one deactivated), five
services (one inactive), six drones in every status, ten orders in every
status, media files, and settings. All sample accounts use the password
`DronePilot123!`, stored as a bcrypt hash.

Until Milestone 4 the frontend login is a mock: any password is accepted, and an
email that contains `admin` opens the admin panel. The real login that checks
the password with `password_verify()` and issues a JWT comes in Milestone 4.

## External API: Open-Meteo

[Open-Meteo](https://open-meteo.com) provides weather data without an API key.
The backend (`backend/services/WeatherService.php`) calls it, never the browser.

- **Vrijeme za let (home page):** enter a city to see the current conditions and
  a 5-day forecast with a verdict for drone flights (good, fly carefully, not
  recommended). Data comes from `GET /backend/weather/flight-conditions` and
  `GET /backend/weather/forecast`.
- **Booking weather check:** `POST /backend/orders` refuses a booking with
  `400 "Drones do not fly in this weather..."` when the hourly forecast for the
  booked place and time shows rain, snow, fog, a thunderstorm, or strong wind.
  Only dates within 7 days are checked.
- **Failures:** every call has a 5 second timeout. Errors go to
  `backend/logs/error.log`, successful calls to `backend/logs/info.log`. The user
  sees a friendly message, and bookings are still accepted when Open-Meteo is
  down.

## Run Locally

Requirements: XAMPP (Apache + MySQL) with PHP 8.4 or newer, and Composer.
The Composer packages (swagger-php 6, Symfony 8) do not run on PHP 8.1.

1. Put the repository in `C:/xampp/htdocs/web_programming`.
2. Install the backend dependencies:

   ```bash
   cd backend
   composer install
   cd ..
   ```

3. Create the configuration file (it is ignored by Git):

   ```bash
   cp backend/config/config.example.php backend/config/config.php
   ```

   Fill in the database user and password. All keys are explained in
   [backend/README.md](backend/README.md).

4. Import the database:

   ```bash
   mysql -u root -p < database/dronepilot_services.sql
   ```

5. Start Apache and MySQL in XAMPP and open:

   - Application: `http://localhost/web_programming/`
   - API: `http://localhost/web_programming/backend/`
   - Swagger UI: `http://localhost/web_programming/backend/public/v1/docs/`

   Without Apache you can also run `php -S localhost:8000 server.php` from the
   repository root and open `http://localhost:8000/` (Swagger at
   `http://localhost:8000/backend/public/v1/docs/`).

6. Optional check of the DAO layer:

   ```bash
   php backend/test/DaoTest.php
   ```

## Project Structure

```text
index.html                SPA shell with the shared navbar
server.php                router for php -S
frontend/
  css/style.css
  js/main.js              hash router, navbar, weather widget
  js/services/            apiService.js, weatherService.js (only code that calls the API)
  views/                  one HTML fragment per page
  static/assets/images/
backend/
  index.php               FlightPHP entry point
  .htaccess               Apache rewrite to index.php
  config/                 AppConfig, Database, Logger, config.example.php
  routes/                 FlightPHP routes, one file per entity
  services/               business logic, WeatherService (Open-Meteo)
  dao/                    BaseDao and one DAO per table
  middleware/             HttpException (JWT middleware in Milestone 4)
  public/v1/docs/         Swagger UI and OpenAPI attribute files
  logs/                   info.log, error.log (not committed)
  test/DaoTest.php
database/                 SQL export and migrations
docs/AI_LOG.md            AI usage per milestone
AGENTS.md                 rules for AI assistants
.github/agents/           Frontend, Backend, Reviewer custom agents
.agents/skills/           update-ai-log skill
```

## API Documentation

All endpoints, request bodies, and status codes are documented with OpenAPI
and shown in Swagger UI:
`http://localhost/web_programming/backend/public/v1/docs/`.
A summary of the endpoints is in [backend/README.md](backend/README.md).

## Deployment

The deployed application link is added in Milestone 5.

## License

This project is for demonstration and educational use.
