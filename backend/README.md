# PHP Backend Setup

## Requirements

- PHP 8.1 or newer
- PDO MySQL extension
- MySQL or MariaDB
- A local PHP development server

## Database setup

1. Copy the example configuration:

   ```bash
   cp backend/config/config.example.php backend/config/config.php
   ```

2. Set the local host, port, database name, username, and password in
   `backend/config/config.php`.

3. Import the SQL export:

   ```bash
   mysql -u root -p < database/dronepilot_services.sql
   ```

The real `backend/config/config.php` is ignored by Git. Only
`backend/config/config.example.php` is commit-ready.

## Run DAO verification

```bash
php backend/test/DaoTest.php
```

The test verifies sample reads and executes create, update, and delete operations
against a temporary setting record.
