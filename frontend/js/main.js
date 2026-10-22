const app = document.getElementById('app');

if (!app) {
    throw new Error('Nedostaje glavni kontejner aplikacije (#app).');
}

const routes = {
    home: { title: 'DronePilot Services', bodyClass: 'bg-light' },
    login: { title: 'Prijava - DronePilot Services', bodyClass: 'bg-light' },
    register: { title: 'Registracija - DronePilot Services', bodyClass: 'auth-body' },
    admin: { title: 'Admin panel - DronePilot', bodyClass: 'admin-body', adminOnly: true },
    drones: { title: 'Naši dronovi - DronePilot', bodyClass: 'bg-light' },
    orders: { title: 'Narudžbe - DronePilot', bodyClass: 'bg-light', adminOnly: true },
    profile: { title: 'Profil - DronePilot', bodyClass: 'bg-light' }
};

let renderVersion = 0;

function getCurrentUser() {
    const savedUser = localStorage.getItem('dronepilot_user');
    if (!savedUser) {
        return null;
    }

    try {
        return JSON.parse(savedUser);
    } catch (error) {
        console.error('Neispravni podaci korisnika u lokalnoj pohrani.', error);
        return null;
    }
}

function getCurrentRoute() {
    const routeMatch = window.location.hash.match(/^#\/([^?#]*)/);
    return routeMatch ? routeMatch[1] || 'home' : 'home';
}

function updateNavigation(route) {
    const user = getCurrentUser();
    const role = user ? user.role : 'guest';
    const navDrones = document.getElementById('navDrones');
    const navOrders = document.getElementById('navOrders');
    const navProfile = document.getElementById('navProfile');

    if (navDrones) {
        navDrones.classList.remove('d-none');
    }
    if (navOrders) {
        navOrders.classList.toggle('d-none', role !== 'admin');
    }
    if (navProfile) {
        navProfile.classList.toggle('d-none', role === 'guest');
    }

    const loginButton = document.getElementById('loginBtn');
    const registerButton = document.getElementById('registerBtn');
    const logoutButton = document.getElementById('logoutBtn');
    const userBadge = document.getElementById('userBadge');

    if (loginButton) {
        loginButton.classList.toggle('d-none', role !== 'guest');
    }
    if (registerButton) {
        registerButton.classList.toggle('d-none', role !== 'guest');
    }
    if (logoutButton) {
        logoutButton.classList.toggle('d-none', role === 'guest');
    }
    if (userBadge) {
        userBadge.classList.toggle('d-none', role === 'guest');
        userBadge.textContent = role === 'admin' ? 'Admin' : 'User';
    }

    document.querySelectorAll('.nav-link[href^="#/"]').forEach(function (link) {
        const linkRoute = link.getAttribute('href').slice(2);
        link.classList.toggle('active', linkRoute === route);
    });
}

function showRouteError(message) {
    app.replaceChildren();
    const main = document.createElement('main');
    main.className = 'container py-5';
    const heading = document.createElement('h1');
    heading.className = 'h3';
    heading.textContent = 'Stranica nije dostupna';
    const description = document.createElement('p');
    description.className = 'text-muted';
    description.textContent = message;
    const link = document.createElement('a');
    link.href = '#/home';
    link.className = 'btn btn-primary';
    link.textContent = 'Nazad na početnu';
    main.append(heading, description, link);
    app.append(main);
    app.setAttribute('aria-busy', 'false');
}

async function renderRoute() {
    const route = getCurrentRoute();
    const page = routes[route];
    const version = ++renderVersion;

    if (!page) {
        showRouteError('Traženi prikaz ne postoji.');
        return;
    }

    const user = getCurrentUser();
    if (page.adminOnly && (!user || user.role !== 'admin')) {
        alert('Samo administrator može pristupiti ovoj stranici.');
        window.location.hash = '#/login';
        return;
    }

    app.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(`frontend/views/${route}.html`);
        if (!response.ok) {
            throw new Error(`Učitavanje prikaza nije uspjelo (${response.status}).`);
        }

        const markup = await response.text();
        if (version !== renderVersion) {
            return;
        }

        const viewDocument = new DOMParser().parseFromString(markup, 'text/html');
        viewDocument.querySelectorAll('script').forEach(function (script) {
            script.remove();
        });

        document.title = page.title;
        document.body.className = page.bodyClass;
        app.innerHTML = viewDocument.body.innerHTML;
        app.setAttribute('aria-busy', 'false');
        updateNavigation(route);
        window.scrollTo(0, 0);
    } catch (error) {
        if (version !== renderVersion) {
            return;
        }
        console.error(`Nije moguće učitati prikaz "${route}".`, error);
        showRouteError('Došlo je do greške prilikom učitavanja stranice. Pokušajte ponovo.');
    }
}

app.addEventListener('submit', function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.id === 'loginForm') {
        event.preventDefault();
        const email = form.querySelector('#email').value.trim().toLowerCase();
        const password = form.querySelector('#password').value.trim();

        if (!email || !password) {
            alert('Unesite email i lozinku.');
            return;
        }

        const isAdmin = email.includes('admin');
        const role = isAdmin ? 'admin' : 'user';
        localStorage.setItem('dronepilot_user', JSON.stringify({
            email,
            role,
            name: isAdmin ? 'Admin korisnik' : 'Haris Kovačević'
        }));
        window.location.hash = isAdmin ? '#/admin' : '#/home';
    } else if (form.id === 'registerForm') {
        event.preventDefault();
        alert('Registracija je simulirana. U budućnosti će se povezati sa bazom korisnika.');
    }
});

app.addEventListener('click', function (event) {
    const target = event.target instanceof Element ? event.target : null;
    const logoutButton = target ? target.closest('#logoutBtn') : null;
    if (logoutButton) {
        localStorage.removeItem('dronepilot_user');
        window.location.hash = '#/home';
    }
});

window.addEventListener('hashchange', function () {
    if (window.location.hash.startsWith('#/')) {
        renderRoute();
    }
});

renderRoute();