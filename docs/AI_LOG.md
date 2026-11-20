# AI Log

This file records AI-assisted work performed during development of the
DronePilot Services project. Each completed milestone has its own section.

## Milestone 1: Repository documentation and AI context

**Date:** October 30, 2026

**AI tools used:**
- GitHub Copilot in VS Code / Copilot SDK: reviewed the repository structure,
  existing documentation, hash router, frontend views, and current implementation
  state.
- AI chat: drafted repository rules and the retrospective AI log.
- Manual file review: verified paths, technologies, and project files before
  writing documentation.

**Most important prompts:**
- "Implement or verify the root AGENTS.md with the project overview, technology
  stack, architecture, folder structure, naming conventions, and AI rules."
- "Add docs/AI_LOG.md with milestone sections, used AI tools, important prompts,
  and at least two cases where AI-generated work was wrong or poor."
- "Write all project content in English."
- "Keep the log limited to one milestone because only one milestone was completed."

**Result:**
The root [AGENTS.md](../AGENTS.md)
contains the project overview, technology stack, architecture rules, directory
structure, naming conventions, implementation rules, and validation guidance.
This log documents the AI tools, prompts, lessons learned, and corrections for
the completed documentation milestone.

### Incorrect case 1: Documentation initially mixed the actual and planned state

- **What was wrong:** An earlier draft presented planned PHP and MySQL
  technologies as if they were already implemented, even though the repository
  currently contains a frontend prototype and placeholder backend folders.
- **How it was noticed:** Manual review of the repository showed that frontend
  files contain the working prototype, while the backend folders do not contain
  a completed PHP implementation. Authentication is currently handled in
  `frontend/js/main.js` with `localStorage`.
- **How it was fixed:** The documentation now clearly labels PHP and MySQL as
  planned technologies and distinguishes actual frontend behavior from future
  backend implementation.

### Incorrect case 2: The initial log described several uncompleted milestones

- **What was wrong:** The AI-generated log incorrectly represented route
  development, authentication work, and documentation work as separate completed
  milestones, even though the current task completed only the documentation
  milestone.
- **How it was noticed:** Reviewing the user's correction showed that this log
  and the repository changes contain no evidence of separate completed
  implementation milestones.
- **How it was fixed:** The log was rewritten to contain exactly one milestone
  section for repository documentation and AI context.

## Milestone 2: Database and DAO Layer

**Deadline:** November 1, 2026, with a final submission deadline of November 2,
2026 at 8:00 AM.

**AI tools used:**
- GitHub Copilot in VS Code / Copilot SDK: reviewed the provided
  `database.png`, created the MySQL schema, implemented PDO configuration,
  generated Base DAO functionality, and added entity DAO classes.
- AI chat: suggested DAO verification coverage and scope boundaries.
- Manual file and schema review: verified table relationships, foreign keys,
  sample records, prepared statements, secret handling, and English filenames.

**Most important prompts:**
- "Write PHP and implement the Milestone 2 database and DAO requirements:
  MySQL SQL export, base DAO, entity DAOs, and CRUD behavior."
- "Use the entities shown in database.png and add at least five entities with
  primary and foreign keys."
- "Read database credentials from config.php, but commit config.example.php
  instead of real credentials."
- "Use prepared statements for database operations."
- "Add verification for the DAO classes, keep HTTP routing for later milestones,
  and document the milestones in English."

**Result:**
The project now includes a six-table MySQL schema plus a `settings` table,
sample data, PDO database configuration, reusable `BaseDao`, and DAO classes for
roles, users, services, drones, orders, media, and settings.

Deliverables added:

- `database/dronepilot_services.sql`
- `backend/config/config.example.php`
- `backend/config/Database.php`
- `backend/dao/BaseDao.php`
- Six or more entity-specific DAO classes
- `backend/test/DaoTest.php`

### Incorrect case 1: Database credentials were initially at risk of being committed

- **What was wrong:** An implementation approach could have placed real database
  credentials directly in a PHP file tracked by Git.
- **How it was noticed:** Reviewing the repository `.gitignore` and milestone
  requirement showed that the real configuration file must remain uncommitted,
  while an example file is commit-ready.
- **How it was fixed:** `backend/config/config.php` is ignored and contains local
  placeholders, while `backend/config/config.example.php` documents the expected
  configuration structure without secrets.

### Incorrect case 2: HTTP routing was added before the required milestone

- **What was wrong:** A custom JSON API dispatcher was created while working on
  Milestone 2, and it was not the FlightPHP implementation required for the
  project.
- **How it was noticed:** Reviewing `docs/ProjectRequirements.txt` showed that
  custom REST routing is outside Milestone 2. FlightPHP routes and OpenAPI are
  assigned to Milestone 3.
- **How it was fixed:** The dispatcher and API files were removed. The Milestone
  2 implementation remains database and DAO focused, while FlightPHP is reserved
  for the later presentation-layer milestone.

## Milestone 3: FlightPHP API, services, OpenAPI and external API

**Deadline:** Monday, November 23, 2026 at 8:00 AM.

**AI tools used:**
- Claude Code (Anthropic) in VS Code, first session: wrote the service layer,
  FlightPHP routes, `AppConfig`, `Logger`, `WeatherService`, the OpenAPI
  attribute files, and the `orders.location` migration. That session had no
  Composer dependencies and no MySQL, so it could not run the code.
- Claude Code (Anthropic) in VS Code, second session: imported the database,
  started the API with `php -S localhost:8000 server.php`, tested every endpoint
  with curl, generated the Swagger document, fixed the bugs listed below, and
  updated the documentation.
- Manual review: checked status codes, error messages, log files, and the
  Swagger UI in the browser.

**Most important prompts:**
- "Implement Milestone 3: FlightPHP routes for every entity, a service layer
  with business logic, and OpenAPI documentation with Swagger UI."
- "Integrate an external API (Open-Meteo) with timeouts, error handling,
  configuration-based keys, and info/error logs."
- "Refuse an order when the weather forecast for the booked time and place
  makes drone flights unsafe, but keep accepting orders when the weather API is
  down."
- "Run and test the backend: import the SQL, test CRUD and status codes
  200/201/400/404/409/500 for every entity, the weather endpoints, and the
  bad-weather booking refusal. Check that Swagger generates without errors."
- "Modernize the landing page design: sky, clouds and drone symbols." (kept
  the HTML/CSS/JS separation: inline SVG drone in `home.html`, all styles and
  animations in `style.css`, reduced-motion support)
- "Show the wind in km/h instead of m/s." (the backend now asks Open-Meteo
  for km/h, so the widget, the booking refusal message, and Swagger all use
  the same unit)

**Result:**
- REST API on FlightPHP for roles, users, services, drones, orders, media, and
  settings (41 operations), plus `/weather/flight-conditions` and
  `/weather/forecast`.
- `BaseService` with shared CRUD and validation helpers; one service per entity
  with business rules (unique values -> 409, records in use cannot be deleted
  -> 409, final order statuses, no bookings in the past, automatic order number
  and price).
- Open-Meteo integration: current conditions, daily forecast, and a booking
  weather check for the scheduled hour plus the next two hours.
- OpenAPI 3 documentation generated by swagger-php 6.12 and served by Swagger UI
  at `/backend/public/v1/docs/`.
- Frontend: one shared navbar in `index.html` and the "Vrijeme za let" widget
  on the home page (current weather and a 5-day flight forecast through
  `frontend/js/services/weatherService.js`, with a fallback message when the
  weather service is unavailable).
- Verified: clean SQL import, migration on a pre-Milestone 3 database,
  `backend/test/DaoTest.php`, and all endpoints with curl.

### Incorrect case 1: `Config.php` next to `config.php`

- **What was wrong:** The AI created a class file `backend/config/Config.php`
  next to the existing `backend/config/config.php`.
- **How it was noticed:** Windows file names are not case-sensitive, so the two
  files are the same path and one would overwrite the other.
- **How it was fixed:** The class was renamed to `AppConfig` in
  `backend/config/AppConfig.php`.

### Incorrect case 2: SQL expression passed as a column name

- **What was wrong:** The order number generator called
  `countBy('YEAR(created_at)', ...)`. `BaseDao::validateColumns()` only accepts
  plain column names (this protects against SQL injection), so the call always
  threw an exception.
- **How it was noticed:** Reading `BaseDao::validateColumns()` while reviewing
  the generator.
- **How it was fixed:** The generator now starts from the number of existing
  orders and increases the number until `getOneBy('order_number', ...)` finds a
  free value.

### Incorrect case 3: Boolean `false` for a MySQL BOOLEAN column

- **What was wrong:** A PHP `false` value sent to a MySQL `BOOLEAN`
  (`TINYINT(1)`) column through PDO would be sent as an empty string.
- **How it was noticed:** Review of how PDO binds PHP booleans with emulated
  prepares turned off.
- **How it was fixed:** `BaseService::cleanBool()` accepts `true/false`, `1/0`,
  and `"true"/"false"` and stores `1` or `0`.

### Incorrect case 4: Every weather call returned 503 locally

- **What was wrong:** The untested `WeatherService` failed on every request.
  The local Windows PHP build has no CA certificate file, so cURL rejected the
  HTTPS certificate of Open-Meteo.
- **How it was noticed:** The first test run returned 503 for every weather
  endpoint. `backend/logs/error.log` contained
  `unable to get local issuer certificate`. The fallback itself worked
  correctly: the error was logged and the client got a friendly 503.
- **How it was fixed:** A `ca_bundle_path` setting was added to `config.php`.
  When it is set, cURL uses that certificate file. SSL verification was not
  turned off. On Linux servers the setting stays empty.

### Incorrect case 5: Server time zone was UTC

- **What was wrong:** PHP ran in UTC, while scheduled dates in the database are
  local time (Europe/Sarajevo). The "date cannot be in the past" rule and the
  log timestamps were off by one hour.
- **How it was noticed:** Log entries showed 23:11 when the local time was 00:11.
- **How it was fixed:** `backend/index.php` sets the time zone from the new
  `timezone` setting in `config.php` (default `Europe/Sarajevo`).

### Incorrect case 6: Invalid query values were silently ignored

- **What was wrong:** `GET /services?active=maybe` returned all services with
  200 instead of reporting an invalid parameter.
- **How it was noticed:** Testing the filters before documenting them.
- **How it was fixed:** An invalid `active` value now returns 400.

### Incorrect case 7: Navbar copied into every view

- **What was wrong:** The hash-routed SPA from Milestone 1 had a navbar copied
  into each view, and the copies were different (the home page had section links,
  other pages did not). Every route change replaced the navbar with another one,
  and the section links (`#usluge`) changed the URL hash that the router uses.
- **How it was noticed:** The student reported that the navbar "breaks" after
  opening Dronovi or a section. After the first fix the browser showed two
  navbars, because a cached old `home.html` still contained its own copy.
- **How it was fixed:** One navbar now lives in `index.html` and `main.js` only
  updates it. Section links use `data-section`, so they scroll without changing
  the route. Views are fetched with `cache: 'no-cache'`. A rule was added to
  AGENTS.md: never copy navigation into views.

### Incorrect case 8: "Odjavi se" did nothing on the home page

- **What was wrong:** Logout set the hash to `#/home`. On the home page the hash
  did not change, `hashchange` did not fire, and the navbar still showed the
  user as logged in.
- **How it was noticed:** The student reported it. A headless Edge test reproduced
  it.
- **How it was fixed:** `logout()` refreshes the navbar directly when the user is
  already on `#/home`.

### Incorrect case 9: Dependencies that do not run on XAMPP

- **What was wrong:** `swagger-php` 6.12 and the Symfony 8 packages it installs
  need PHP 8.4, but XAMPP shipped PHP 8.1. Through Apache the whole API failed
  with a Composer platform error. The AI had only tested with a separate PHP 8.4
  and `php -S`, so it did not see this.
- **How it was noticed:** The student opened the app through
  `http://localhost/web_programming/` and the weather widget showed an error.
- **How it was fixed:** PHP 8.4.28 (Thread Safe, x64) was installed in
  `C:/xampp/php84` and Apache was switched to it. The old `C:/xampp/php` was kept.
  `backend/.htaccess` was added for FlightPHP routing and blocks HTTP access to
  `config/`, `logs/`, and `vendor/`; before that, `logs/error.log` was readable
  in the browser.

### Incorrect case 10: Missing AI agent and skill files

- **What was wrong:** Lab 01.01 asks for `.github/agents/frontend.agent.md`,
  `backend.agent.md`, `reviewer.agent.md` and `.agents/skills/update-ai-log/SKILL.md`.
  They were not created in Milestone 1. `backend/logs/` was also not in Git,
  because the `.gitignore` rule `logs/` ignored the folder together with its
  `.gitkeep`.
- **How it was noticed:** Review of the repository against the Lab 01.01 checklist.
- **How it was fixed:** The three agents and the skill were added and adapted to
  this project. The `.gitignore` rule was changed to `/logs/`, so
  `backend/logs/.gitkeep` is tracked and the log files stay ignored.

### Incorrect case 11: `.htaccess` blocked the `/services` API route

- **What was wrong:** The first `backend/.htaccess` blocked every URL that starts
  with a PHP source folder name (`config`, `services`, `dao`, ...). The API
  resource `GET /services` has the same name as the folder `backend/services/`,
  so Apache answered 403 and the admin panel could not load any data.
- **How it was noticed:** The admin panel showed "Podaci se trenutno ne mogu
  učitati". The browser console showed `ApiError: HTTP 403` from
  `adminService.js`. The earlier curl test had only checked `/roles`,
  `/orders`, and the weather routes.
- **How it was fixed:** Only real files inside those folders are blocked now.
  Every other URL goes to FlightPHP, and `DirectorySlash Off` stops Apache from
  redirecting `/services` to `/services/`. All 12 API routes were tested again
  (200), and config, logs, vendor, source files, and `composer.json` return 403.

### Incorrect case 12: Admin dashboard with invented numbers

- **What was wrong:** The admin dashboard from Milestone 1 showed hard-coded
  numbers (128 orders, €18.4k, 96 clients) and invented customers, and three
  sidebar links pointed to `#`.
- **How it was noticed:** The student reported that the sidebar buttons do not
  work.
- **How it was fixed:** Every sidebar link opens a section of the admin panel
  (`#/admin?section=orders`). The statistics and tables are calculated from API
  data loaded by `adminService.js`. The sample data in
  `database/dronepilot_services.sql` was extended (7 users, 5 services,
  6 drones, 10 orders, 7 media files, 5 settings). All sample users got the
  known password `DronePilot123!`, and the SQL file now starts with
  `SET NAMES utf8mb4` so names with č, ć, ž import correctly.
