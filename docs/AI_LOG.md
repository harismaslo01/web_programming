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
The root [AGENTS.md](/run/media/haris/01DD67EDDC6DB3F0/Users/Haris/Desktop/web_programming/AGENTS.md)
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
