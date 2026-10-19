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
├── login.html
├── register.html
├── admin.html
├── drones.html
├── profile.html
├── orders.html
├── frontend/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
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
├── README.md
└── 
```

## Technology Stack

- HTML5
- CSS3
- Vanilla JavaScript
- Bootstrap 5.3 (CDN)
- Bootstrap Icons
- LocalStorage-based mock authentication

## How to Run

Because this is a static prototype, it can be opened directly in a browser. For best results, serve the project using a local web server.

Example:

```bash
python -m http.server 8000
```

Then open:

```text
http://localhost:8000
```

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

## License

This project is for demonstration and educational use.
