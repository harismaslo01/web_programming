# AGENTS.md - DronePilot Services

## Project Overview

DronePilot Services is a responsive PHP, JavaScript, and MySQL single-page
application for drone rental and aerial services. The final application will
support user and administrator roles, at least five managed entities, a REST API,
external API integration, logging, deployment, and role-based access.

## Technology Stack

- Frontend: HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.3, and Bootstrap Icons.
- Frontend architecture: hash-routed SPA with HTML views loaded by JavaScript.
- Backend: Vanilla PHP using PDO and the FlightPHP framework.
- Database: MySQL/MariaDB.
- Local server: XAMPP Apache with PHP 8.4 (`C:/xampp/php84`), or
  `php -S localhost:8000 server.php`. The Composer packages need PHP 8.4.
- Entities: roles, users, services, drones, orders, media, settings.
- Roles: admin and user.
- External API: Open-Meteo (weather), called only from `WeatherService`.
- Authentication: JWT is mandatory for protected routes.
- API documentation: OpenAPI documentation with Swagger UI.
- Additional libraries: Bootstrap, Highcharts, DataTables, and Composer packages
  are allowed when they match the project requirements.

Do not describe planned features as implemented. Clearly distinguish completed
Milestone work from later milestones.

## Project Architecture

- `index.html` is the SPA shell. It contains the one shared navbar
  (`#mainNavbar`) and loads the scripts once.
- `frontend/views/` contains HTML page fragments. Views never contain their own
  navbar; `main.js` updates the shared navbar (active link, login state).
- `frontend/css/style.css` contains project CSS.
- `frontend/js/main.js` contains the current hash router and shared UI behavior.
- `frontend/js/services/` contains the only code that calls the backend:
  `apiService.js` (`apiGet()`, timeout, `ApiError`) and one service per feature
  (`weatherService.js`, `adminService.js`).
- The root `.htaccess` sends `Cache-Control: no-cache` for HTML, JS, and CSS, so
  browsers always revalidate frontend files instead of showing an old version.
- `backend/.htaccess` sends Apache requests to FlightPHP and blocks direct access
  to `config/`, `logs/`, `vendor/`, and the PHP source folders.
- `.github/agents/` contains the Frontend, Backend, and Reviewer custom agents;
  `.agents/skills/update-ai-log/SKILL.md` is the AI log skill.
- `server.php` is the router script for `php -S localhost:8000 server.php`; it
  sends `/backend/...` requests to `backend/index.php` and serves Swagger UI.
- `backend/index.php` is the FlightPHP entry point: it sets the time zone,
  registers the global JSON error handlers, services, and route files.
- `backend/config/` contains database and secret configuration:
  `config.php` (ignored by Git), `config.example.php`, `AppConfig.php`
  (reads `config.php`; named this way because `Config.php` and `config.php`
  collide on Windows), `Database.php` (PDO connection), and `Logger.php`
  (`Logger::info()` -> `logs/info.log`, `Logger::error()` -> `logs/error.log`).
- `backend/dao/` contains database access classes. `BaseDao` provides CRUD plus
  `countBy()` and `getOneBy()`.
- `backend/services/` contains application business logic and external API logic.
  `BaseService` provides shared CRUD, 404 handling, the `validate()`,
  `beforeDelete()`, and `present()` hooks, and the `clean*()` validation helpers.
  `WeatherService` integrates the Open-Meteo API.
- `backend/routes/` contains FlightPHP REST routes, one file per entity, plus
  `RouteHelpers.php` (`getRequestBody()`).
- `backend/middleware/` contains authentication, authorization, logging, and
  error-handling middleware. `HttpException` carries 4xx/5xx status codes.
- `backend/public/v1/docs/` contains Swagger UI, `swagger.php` (generates the
  OpenAPI JSON), `doc_setup.php` (API info and schemas), and `paths/` (one
  `*Paths.php` file with swagger-php attributes per route file).
- `backend/logs/` contains runtime logs and must not be committed.
- `database/migrations/` contains SQL changes for databases imported before a
  milestone changed the schema.
- `database/` contains the exported MySQL schema and sample data.
- `docs/` contains project documentation and `ProjectRequirements.txt`.

## Required Folder Structure

```text
frontend/
  css/
  js/
  views/
  static/
backend/
  routes/
  services/
  dao/
  middleware/
  config/
  logs/
database/
docs/
```

## Naming Conventions

- JavaScript functions use `camelCase`.
- DOM IDs use `camelCase`.
- CSS classes use descriptive names with hyphens when appropriate.
- PHP class names use `PascalCase`.
- PHP methods use `camelCase`.
- Route and entity names use descriptive lowercase names.
- SQL tables use lowercase plural names.
- HTML fragments should match their route or page concept.
- Use four spaces for JavaScript indentation.

## Milestone Rules

- Implement the database and DAO work before creating presentation routes.
- Milestone 2 must provide an SQL export with at least five entities and CRUD
  DAO classes with prepared statements.
- Milestone 3 must use FlightPHP routes, services, OpenAPI/Swagger, and an external
  API. FlightPHP may not be introduced or described as working before its
  milestone implementation is complete.
- Milestone 4 must implement JWT authentication, role authorization, reusable
  middleware, info/error logs, and AJAX-connected frontend entities.
- Milestone 5 must add frontend/backend validations and deployment.
- Complete milestone implementation in a separate milestone branch.

## Milestone Status

- Milestone 1 (documentation, AI context): completed.
- Milestone 2 (database export, DAO layer): completed.
- Milestone 3 (FlightPHP routes, services, OpenAPI/Swagger, Open-Meteo external
  API, booking weather check): completed on branch `milestone3`. The home page
  shows the "Vrijeme za let" weather widget through `weatherService.js`, and
  the admin panel (`#/admin?section=...`) lists all entities through
  `adminService.js` (read-only until Milestone 4). The API
  has no authentication yet; every endpoint is public until Milestone 4. The
  other frontend pages still use static data and mock login.
- Milestone 4 (JWT, roles, middleware, request logging, AJAX frontend including
  the booking form and weather message): not started.
- Milestone 5 (validations on both sides, deployment): not started.

## AI Assistant Rules

1. Keep HTML, CSS, and JavaScript separate.
2. Do not add inline `<style>` blocks or inline JavaScript to HTML views.
3. Put custom CSS in `frontend/css/style.css`.
4. Put shared frontend behavior in `frontend/js/main.js`.
5. Keep backend and frontend code separate.
6. Use PDO and prepared statements for every SQL operation.
7. Never concatenate untrusted data into SQL.
8. Do not implement custom route dispatchers in place of FlightPHP.
9. Implement REST routes in `backend/routes/`, business logic in
   `backend/services/`, and SQL access in `backend/dao/`.
10. Services must be the only layer that calls DAOs in later milestones.
11. Register reusable FlightPHP middleware rather than copying middleware logic
    into every route.
12. Store database credentials, JWT secrets, and external API keys outside Git.
    Commit only `backend/config/config.example.php`.
13. Ignore `.env` files, secret configuration, logs, vendor dependencies, and
    local cache directories.
14. Hash passwords with PHP password hashing APIs and verify them with
    `password_verify()`.
15. JWT-protected routes must return 401 for missing or invalid credentials and
    403 for insufficient roles.
16. Every entity must be manageable through the final frontend using AJAX calls.
17. Views must not perform AJAX directly; use frontend service files.
18. No final frontend entity data may remain static or hard-coded.
19. API endpoints must return JSON and documented HTTP status codes.
20. Document every endpoint with OpenAPI/Swagger.
21. External API calls must have timeouts, error handling, fallback UI behavior,
    configuration-based API keys, and info/error log entries.
22. Use server-side validation and equivalent client-side validation for forms.
23. The application must be responsive and deployable.
24. Write all project documentation and new source comments in English.
25. Update `docs/AI_LOG.md` for every milestone, including tools, prompts, and at
    least two corrected AI mistakes with what was wrong, how it was noticed, and
    how it was fixed.
26. Do not add new libraries, frameworks, or Composer packages without asking
    first.
27. Do not change the database schema without asking first. Every schema change
    needs an updated `database/dronepilot_services.sql` and a migration in
    `database/migrations/`.
28. Do not edit files in `backend/logs/`.
29. Keep one shared navbar in `index.html`; never copy navigation into views.
30. Make small changes and explain what changed and why, so the student can
    review and defend every line.

## Validation

- Export the database as `database/dronepilot_services.sql`.
- Verify primary and foreign keys and sample records.
- Verify all DAO create, read, update, and delete operations against MySQL.
- Import the SQL file on a clean database and run backend tests.
- Start the API with `php -S localhost:8000 server.php` and check every endpoint
  (status codes 200/201/400/404/409/500/503) and that Swagger at
  `/backend/public/v1/docs/` loads without errors.
- Validate HTML, navigation, responsive layout, browser console output, images,
  and route behavior.
- Before each milestone submission, verify the required files are committed to
  the milestone branch and that `config.php` and logs are not committed.
