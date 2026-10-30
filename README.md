# DronePilot Services

DronePilot Services is a drone-based service platform for booking aerial photography, inspection, mapping, and related professional drone operations. The project currently includes a static frontend prototype with user and admin flows, role-based navigation, and mock authentication for demonstration purposes.

## Overview

The platform is designed to support:

- Booking aerial photography and video services
- Scheduling and tracking service appointments
- Managing drone-based inspection projects
- Monitoring project status and activity
- Handling user and administrator access levels
- Supporting future integration with a real backend and database

## Main Features

### User Features
- Landing page with service overview and drone catalog
- Service booking-oriented layout and polished marketing presentation
- User profile page
- Access to public content and personalized account area
- Mock user login flow via local storage

### Admin Features
- Restricted admin dashboard
- Access control based on role
- Admin-only pages such as orders and dashboard views
- Mock admin login flow

### Current Admin Access
The app currently allows admin access by logging in with an email that contains the admin identifier, for example:

- admin@dronpilot.ba

The mock authentication system stores the user role in local storage and redirects administrators to the admin panel automatically.

## Project Structure

```text
/
├── index.html
├── frontend/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   ├── views/
│   │   ├── home.html
│   │   ├── login.html
│   │   ├── register.html
│   │   ├── admin.html
│   │   ├── drones.html
│   │   ├── profile.html
│   │   └── orders.html
│   └── static/
│       └── assets/
│           └── images/
├── backend/
│   ├── config/
│   ├── dao/
│   ├── logs/
│   ├── middleware/
│   ├── routes/
│   └── services/
├── AGENTS.md
├── .gitignore
└── README.md
```

## Technology Stack

- HTML5
- CSS3
- Vanilla JavaScript
- Bootstrap 5.3 (CDN)
- Bootstrap Icons
- LocalStorage-based mock authentication

## Frontend routing

The frontend is a hash-routed single-page application. `index.html` loads views
from `frontend/views/` without reloading the document; routes use hashes such as
`#/drones` and `#/login`. Keep view markup in those HTML fragments and put
shared behavior in `frontend/js/main.js`.

## How to Run

Because the router fetches view fragments, serve this static prototype using a local web server.

Example:

```bash
python -m http.server 8000
```

Then open:

```text
http://localhost:8000
```

The views are fetched at runtime, so use a local web server rather than opening
`index.html` directly as a `file://` URL.

## Demo Login

### User Login
Use a non-admin email such as:

- haris@stu.ibu.edu.ba

### Admin Login
Use:

- admin@dronpilot.ba

The mock system will route the login based on the email and store the role in local storage.

## Notes

This project is currently a frontend prototype and not yet connected to a real backend database. The next steps may include:

- real authentication system
- MySQL integration
- admin dashboard with CRUD operations
- order and invoice management
- payment and scheduling logic
- real user session handling

## Database Entities and Relationships

The Milestone 2 MySQL schema contains seven entities and sample records:

- `roles`: Admin and User roles.
- `users`: user accounts linked to `roles.role_id`.
- `services`: available aerial service types.
- `drones`: drone inventory linked to service orders.
- `orders`: customer bookings linked to users, services, and drones.
- `media`: uploaded files linked to orders.
- `settings`: application configuration values.

Main relationships:

- `users.role_id -> roles.id`
- `orders.user_id -> users.id`
- `orders.service_id -> services.id`
- `orders.drone_id -> drones.id`
- `media.order_id -> orders.id`

The draft ERD is [database.png](database.png), and the importable schema is
[database/dronepilot_services.sql](database/dronepilot_services.sql).

## Roles and Test Accounts

- Admin: `admin@dronpilot.ba`
- Regular User: `haris@stu.ibu.edu.ba`

These accounts currently demonstrate the frontend prototype. Real backend
authentication and JWT access will be implemented in the authentication milestone.

## Local Database Setup

1. Copy the configuration template:

   ```bash
   cp backend/config/config.example.php backend/config/config.php
   ```

2. Update the local database credentials in `backend/config/config.php`.
   Do not commit this file.

3. Import the schema and sample data:

   ```bash
   mysql -u root -p < database/dronepilot_services.sql
   ```

4. Run the DAO verification script when PHP and the PDO MySQL extension are
   available:

   ```bash
   php backend/test/DaoTest.php
   ```

## Future API Documentation and Deployment

Swagger/OpenAPI documentation and the deployed application link will be added
when the FlightPHP API and deployment milestones are completed. The README will
then include:

- the Swagger documentation URL;
- the public application URL;
- the external API and supported feature;
- final test accounts for each role.

## License

This project is for demonstration and educational use.
