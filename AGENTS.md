# Project Overview: DronePilot Services
Web aplikacija za iznajmljivanje dronova i uslužno snimanje iz zraka. Aplikacija ima odvojene uloge (Admin i User).

## Technology Stack
- **Frontend:** HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.3 (CDN)
- **Backend (Planirano):** Vanilla PHP (bez framework-a)
- **Database (Planirano):** MySQL / MariaDB (kroz PDO)

## Project Directory Structure
/
├── index.html                    # Početna stranica
├── login.html                    # Stranica za prijavu
├── register.html                 # Stranica za registraciju
├── admin.html                   # Admin panel
├── drones.html                  # Katalog dronova
├── profile.html                 # Profil korisnika
├── orders.html                  # Stranica narudžbi
├── /frontend
│   ├── /css
│   │   └── style.css           # Prilagođeni CSS stilovi
│   ├── /js
│   │   └── main.js             # JavaScript skripte
│   └── /static
│       └── /assets
│           └── /images         # Slike, ikone i grafički elementi
├── /backend
│   ├── /config
│   ├── /dao
│   ├── /logs
│   ├── /middleware
│   ├── /routes
│   └── /services
├── .gitignore                  # Ignorirani fajlovi
└── AGENTS.md                   # AI Context File

## Rules for AI Assistant
1. **Separation of Concerns:** HTML, CSS i JavaScript moraju biti u odvojenim fajlovima (nema inline stilova niti skripti u HTML-u osim CDN linkova).
2. **Bootstrap Usage:** Koristiti Bootstrap 5 komponente za UI elemente. Ako Bootstrap JS pravi problem u lokalnom okruženju, dozvoljeno je koristiti čisti JavaScript i CSS za interakcije (poput modala).
3. **Clean Code:** Piši jasan, komentarisan kod sa čistom semantikom.