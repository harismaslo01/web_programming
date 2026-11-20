const app = document.getElementById('app');
const navbar = document.getElementById('mainNavbar');

if (!app || !navbar) {
    throw new Error('Nedostaje glavni kontejner aplikacije (#app) ili navigacija (#mainNavbar).');
}

/*
 * Hash routes. Each route loads frontend/views/<route>.html into #app.
 * showNavbar: false hides the shared navbar (login and register have their own layout).
 */
const routes = {
    home: { title: 'DronePilot Services', bodyClass: 'bg-light' },
    login: { title: 'Prijava - DronePilot Services', bodyClass: 'bg-light', showNavbar: false },
    register: { title: 'Registracija - DronePilot Services', bodyClass: 'auth-body', showNavbar: false },
    admin: { title: 'Admin panel - DronePilot', bodyClass: 'admin-body', adminOnly: true },
    drones: { title: 'Naši dronovi - DronePilot', bodyClass: 'bg-light' },
    orders: { title: 'Narudžbe - DronePilot', bodyClass: 'bg-light', adminOnly: true },
    profile: { title: 'Profil - DronePilot', bodyClass: 'bg-light' }
};

let renderVersion = 0;
// Section of the home page to scroll to after #/home has been rendered (e.g. "usluge").
let pendingSection = null;

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

/* ---------- Shared navbar ---------- */

/*
 * The navbar lives in index.html and is never replaced by a view.
 * This function only shows/hides items for the current user and marks the active link.
 */
function updateNavigation(route) {
    const page = routes[route];
    navbar.classList.toggle('d-none', Boolean(page && page.showNavbar === false));

    const user = getCurrentUser();
    const role = user ? user.role : 'guest';

    document.getElementById('navOrders').classList.toggle('d-none', role !== 'admin');
    document.getElementById('navAdmin').classList.toggle('d-none', role !== 'admin');
    document.getElementById('navProfile').classList.toggle('d-none', role === 'guest');

    document.getElementById('loginBtn').classList.toggle('d-none', role !== 'guest');
    document.getElementById('registerBtn').classList.toggle('d-none', role !== 'guest');
    document.getElementById('logoutBtn').classList.toggle('d-none', role === 'guest');

    const userBadge = document.getElementById('userBadge');
    userBadge.classList.toggle('d-none', role === 'guest');
    userBadge.textContent = role === 'admin' ? 'Admin' : 'User';

    // Page links are active on their route; section links only after they are clicked.
    navbar.querySelectorAll('.nav-link').forEach(function (link) {
        const isActive = !link.dataset.section && link.getAttribute('href') === `#/${route}`;
        setLinkActive(link, isActive);
    });
}

function setLinkActive(link, isActive) {
    link.classList.toggle('active', isActive);
    if (isActive) {
        link.setAttribute('aria-current', 'page');
    } else {
        link.removeAttribute('aria-current');
    }
}

/* Marks one navbar link as active, e.g. "Usluge" after scrolling to that section. */
function activateNavLink(activeLink) {
    navbar.querySelectorAll('.nav-link').forEach(function (link) {
        setLinkActive(link, link === activeLink);
    });
}

/* Closes the mobile navbar menu (Bootstrap collapse) if it is open. */
function closeMobileMenu() {
    const openMenu = navbar.querySelector('.navbar-collapse.show');
    if (openMenu) {
        bootstrap.Collapse.getOrCreateInstance(openMenu).hide();
    }
}

/* Scrolls to a section of the current view. Returns false if the section does not exist. */
function scrollToSection(sectionId) {
    const section = sectionId ? document.getElementById(sectionId) : null;
    if (!section) {
        return false;
    }

    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    return true;
}

function logout() {
    localStorage.removeItem('dronepilot_user');

    // Setting the same hash does not fire "hashchange", so the home page is refreshed directly.
    if (getCurrentRoute() === 'home') {
        updateNavigation('home');
        window.scrollTo(0, 0);
    } else {
        window.location.hash = '#/home';
    }
}

/* ---------- Router ---------- */

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
    updateNavigation(getCurrentRoute());
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
        // no-cache: the browser must re-check the view with the server, so an old cached copy is never shown.
        const response = await fetch(`frontend/views/${route}.html`, { cache: 'no-cache' });
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

        if (route === 'home') {
            initWeatherWidget();
        } else if (route === 'admin') {
            initAdminPanel();
        }

        // A section link clicked on another page ("Usluge" on #/drones) scrolls after the render.
        const sectionLink = pendingSection
            ? navbar.querySelector(`.nav-link[data-section="${pendingSection}"]`)
            : null;
        if (pendingSection && scrollToSection(pendingSection)) {
            if (sectionLink) {
                activateNavLink(sectionLink);
            }
        } else {
            window.scrollTo(0, 0);
        }
        pendingSection = null;
    } catch (error) {
        if (version !== renderVersion) {
            return;
        }
        console.error(`Nije moguće učitati prikaz "${route}".`, error);
        showRouteError('Došlo je do greške prilikom učitavanja stranice. Pokušajte ponovo.');
    }
}

/* ---------- Weather widget (home page, external API through our backend) ---------- */

const CITY_PATTERN = /^[\p{L}\p{M} .,'-]+$/u;
const WEEKDAYS = ['ned', 'pon', 'uto', 'sri', 'čet', 'pet', 'sub'];
let weatherRequestVersion = 0;

const FLIGHT_STATUS_LABELS = {
    good: { text: 'Uslovi za let su dobri', badge: 'bg-success', card: 'weather-day-good', icon: 'bi-check-circle-fill' },
    caution: { text: 'Letite oprezno', badge: 'bg-warning text-dark', card: 'weather-day-caution', icon: 'bi-exclamation-triangle-fill' },
    not_recommended: { text: 'Let se ne preporučuje', badge: 'bg-danger', card: 'weather-day-bad', icon: 'bi-x-octagon-fill' }
};

/* WMO weather code (Open-Meteo) -> Bosnian description and Bootstrap icon. */
function describeWeather(code) {
    if (code === null || code === undefined) {
        return { text: 'Nepoznato', icon: 'bi-question-circle' };
    }
    if (code === 0) return { text: 'Vedro', icon: 'bi-sun' };
    if (code <= 2) return { text: 'Djelimično oblačno', icon: 'bi-cloud-sun' };
    if (code === 3) return { text: 'Oblačno', icon: 'bi-cloud' };
    if (code === 45 || code === 48) return { text: 'Magla', icon: 'bi-cloud-fog2' };
    if (code >= 51 && code <= 57) return { text: 'Rosulja', icon: 'bi-cloud-drizzle' };
    if (code >= 61 && code <= 67) return { text: 'Kiša', icon: 'bi-cloud-rain' };
    if (code >= 71 && code <= 77) return { text: 'Snijeg', icon: 'bi-snow' };
    if (code >= 80 && code <= 82) return { text: 'Pljuskovi', icon: 'bi-cloud-rain-heavy' };
    if (code === 85 || code === 86) return { text: 'Snježni pljuskovi', icon: 'bi-cloud-snow' };
    if (code >= 95) return { text: 'Grmljavina', icon: 'bi-cloud-lightning-rain' };
    return { text: 'Nepoznato', icon: 'bi-question-circle' };
}

/* Small DOM helper: element with optional class and text (textContent, never innerHTML). */
function createElement(tag, className, text) {
    const element = document.createElement(tag);
    if (className) {
        element.className = className;
    }
    if (text !== undefined) {
        element.textContent = text;
    }
    return element;
}

function formatNumber(value, unit, decimals = 1) {
    return value === null || value === undefined ? '-' : `${Number(value).toFixed(decimals)} ${unit}`;
}

function initWeatherWidget() {
    const form = document.getElementById('weatherForm');
    if (form) {
        loadWeather(form.querySelector('#weatherCity').value);
    }
}

function setWeatherStatus(content, isError) {
    const status = document.getElementById('weatherStatus');
    if (!status) {
        return;
    }

    status.replaceChildren();
    if (!content) {
        return;
    }

    if (isError) {
        const alertBox = createElement('div', 'alert alert-warning d-inline-flex flex-wrap align-items-center gap-3 mb-0');
        alertBox.append(createElement('span', '', content));
        const retryButton = createElement('button', 'btn btn-sm btn-outline-dark rounded-pill', 'Pokušaj ponovo');
        retryButton.type = 'button';
        retryButton.dataset.action = 'weather-retry';
        alertBox.append(retryButton);
        status.append(alertBox);
    } else {
        const loading = createElement('span', '');
        loading.append(createElement('span', 'spinner-border spinner-border-sm me-2'), content);
        status.append(loading);
    }
}

/* Translates an API error into a message for the user (fallback UI). */
function weatherErrorMessage(error) {
    if (error.status === 400) {
        return 'Unesite ispravan naziv grada.';
    }
    if (error.status === 404 && /Location/i.test(error.message)) {
        return 'Grad nije pronađen. Provjerite naziv i pokušajte ponovo.';
    }
    return 'Vremenska prognoza trenutno nije dostupna. Pokušajte ponovo za nekoliko minuta.';
}

async function loadWeather(cityInput) {
    const city = String(cityInput || '').trim();
    const result = document.getElementById('weatherResult');
    if (!result) {
        return;
    }

    // Client-side validation, same rule as WeatherService::findLocation() on the server.
    if (city === '' || city.length > 100 || !CITY_PATTERN.test(city)) {
        result.classList.add('d-none');
        setWeatherStatus('Unesite ispravan naziv grada (samo slova, razmak, tačka, zarez, crtica).', true);
        return;
    }

    const requestVersion = ++weatherRequestVersion;
    setWeatherStatus('Učitavanje vremenske prognoze...', false);

    try {
        const [conditions, forecast] = await Promise.all([
            WeatherService.getFlightConditions(city),
            WeatherService.getForecast(city, 5)
        ]);

        // Ignore old answers (a newer search was started or the user left the page).
        if (requestVersion !== weatherRequestVersion || !result.isConnected) {
            return;
        }

        renderCurrentWeather(conditions);
        renderForecast(forecast);
        setWeatherStatus(null, false);
        result.classList.remove('d-none');
    } catch (error) {
        if (requestVersion !== weatherRequestVersion || !result.isConnected) {
            return;
        }
        console.error('Vremenska prognoza nije učitana.', error);
        result.classList.add('d-none');
        setWeatherStatus(weatherErrorMessage(error), true);
    }
}

function renderCurrentWeather(data) {
    const container = document.getElementById('weatherCurrent');
    const weather = data.weather;
    const description = describeWeather(weather.weather_code);
    const verdict = FLIGHT_STATUS_LABELS[data.flight.status] || FLIGHT_STATUS_LABELS.caution;
    const place = [data.location.name, data.location.country].filter(Boolean).join(', ');

    const heading = createElement('h3', 'h5 fw-bold mb-1', place);
    const subtitle = createElement('p', 'text-muted small mb-3', 'Trenutno');

    const main = createElement('div', 'd-flex align-items-center gap-3 mb-3');
    main.append(
        createElement('i', `bi ${description.icon} weather-icon text-primary`),
        createElement('div', '')
    );
    main.lastChild.append(
        createElement('div', 'display-6 fw-bold', formatNumber(weather.temperature, '°C', 0)),
        createElement('div', 'text-muted', description.text)
    );

    const badge = createElement('span', `badge rounded-pill ${verdict.badge} mb-3 px-3 py-2`);
    badge.append(createElement('i', `bi ${verdict.icon} me-1`), verdict.text);

    const details = createElement('ul', 'list-unstyled small mb-0 weather-details');
    [
        ['Vjetar', formatNumber(weather.wind_speed, 'km/h', 0)],
        ['Udari vjetra', formatNumber(weather.wind_gusts, 'km/h', 0)],
        ['Padavine', formatNumber(weather.precipitation, 'mm')],
        ['Vidljivost', weather.visibility === null ? '-' : formatNumber(weather.visibility / 1000, 'km')],
        ['Oblačnost', formatNumber(weather.cloud_cover, '%', 0)]
    ].forEach(function ([label, value]) {
        const item = createElement('li', 'd-flex justify-content-between py-1');
        item.append(createElement('span', 'text-muted', label), createElement('strong', '', value));
        details.append(item);
    });

    container.replaceChildren(heading, subtitle, main, badge, details);
}

function renderForecast(data) {
    const container = document.getElementById('weatherForecast');
    container.replaceChildren();

    data.days.forEach(function (day) {
        const description = describeWeather(day.weather_code);
        const verdict = FLIGHT_STATUS_LABELS[day.flight.status] || FLIGHT_STATUS_LABELS.caution;
        // "2026-11-23" -> "pon 23.11." (formatted by hand, not every browser knows the bs-BA locale)
        const [year, month, dayOfMonth] = day.date.split('-').map(Number);
        const weekday = WEEKDAYS[new Date(year, month - 1, dayOfMonth).getDay()];
        const dateLabel = `${weekday} ${dayOfMonth}.${month}.`;

        const column = createElement('div', 'col-6 col-md-4 col-xl');
        const card = createElement('div', `card border-0 shadow-sm rounded-4 h-100 text-center weather-day ${verdict.card}`);
        const body = createElement('div', 'card-body p-3');

        const verdictLine = createElement('div', 'small fw-semibold mt-2 weather-verdict');
        verdictLine.append(createElement('i', `bi ${verdict.icon} me-1`), verdict.text);

        body.append(
            createElement('div', 'fw-semibold', dateLabel),
            createElement('i', `bi ${description.icon} fs-2 text-primary d-block my-1`),
            createElement('div', 'small text-muted', description.text),
            createElement('div', 'fw-bold mt-1',
                `${formatNumber(day.temperature_min, '°', 0)} / ${formatNumber(day.temperature_max, '°', 0)}`),
            createElement('div', 'small text-muted', `Vjetar do ${formatNumber(day.wind_gusts, 'km/h', 0)}`),
            createElement('div', 'small text-muted', `Kiša ${formatNumber(day.precipitation_probability, '%', 0)}`),
            verdictLine
        );
        card.append(body);
        column.append(card);
        container.append(column);
    });
}

/* ---------- Admin panel (#/admin?section=...) ---------- */

const ADMIN_SECTIONS = {
    dashboard: 'Kontrolna tabla',
    services: 'Usluge',
    orders: 'Narudžbe',
    drones: 'Dronovi',
    clients: 'Klijenti',
    settings: 'Postavke'
};

// Status value from the database -> Bosnian label and Bootstrap color.
const ORDER_STATUS_LABELS = {
    draft: { text: 'Nacrt', color: 'secondary' },
    pending: { text: 'Na čekanju', color: 'warning' },
    confirmed: { text: 'Potvrđeno', color: 'primary' },
    in_progress: { text: 'U toku', color: 'info' },
    completed: { text: 'Završeno', color: 'success' },
    cancelled: { text: 'Otkazano', color: 'danger' }
};

const DRONE_STATUS_LABELS = {
    available: { text: 'Dostupan', color: 'success' },
    reserved: { text: 'Rezervisan', color: 'primary' },
    maintenance: { text: 'Na servisu', color: 'warning' },
    retired: { text: 'Povučen', color: 'secondary' }
};

// Data loaded once per visit of the admin page; the status filter re-renders from it.
let adminData = null;

/* Reads a parameter from the hash, e.g. "orders" from #/admin?section=orders */
function getHashParameter(name) {
    const query = window.location.hash.split('?')[1] || '';
    return new URLSearchParams(query).get(name);
}

function getAdminSection() {
    const section = getHashParameter('section');
    return Object.prototype.hasOwnProperty.call(ADMIN_SECTIONS, section) ? section : 'dashboard';
}

async function initAdminPanel() {
    const section = getAdminSection();

    // Sidebar: mark the open section; title: its name.
    app.querySelectorAll('[data-admin-link]').forEach(function (link) {
        link.classList.toggle('active', link.dataset.adminLink === section);
    });
    document.getElementById('adminTitle').textContent = ADMIN_SECTIONS[section];
    app.querySelectorAll('[data-admin-section]').forEach(function (element) {
        element.classList.toggle('d-none', element.dataset.adminSection !== section);
    });

    setAdminStatus('Učitavanje podataka...', false);
    try {
        const data = await AdminService.loadAll();
        // The user may have opened another page while the data was loading.
        if (!document.getElementById('adminTitle')) {
            return;
        }
        adminData = data;
        setAdminStatus(null, false);
        renderAdminSection(section);
    } catch (error) {
        console.error('Admin podaci nisu učitani.', error);
        setAdminStatus('Podaci se trenutno ne mogu učitati. Provjerite da li backend i baza rade.', true);
    }
}

function setAdminStatus(message, isError) {
    const status = document.getElementById('adminStatus');
    if (!status) {
        return;
    }

    status.replaceChildren();
    if (!message) {
        return;
    }

    if (isError) {
        const alertBox = createElement('div', 'alert alert-warning d-flex flex-wrap align-items-center gap-3 mb-0');
        alertBox.append(createElement('span', '', message));
        const retryButton = createElement('button', 'btn btn-sm btn-outline-dark rounded-pill', 'Pokušaj ponovo');
        retryButton.type = 'button';
        retryButton.dataset.action = 'admin-retry';
        alertBox.append(retryButton);
        status.append(alertBox);
    } else {
        const loading = createElement('div', 'text-muted');
        loading.append(createElement('span', 'spinner-border spinner-border-sm me-2'), message);
        status.append(loading);
    }
}

function renderAdminSection(section) {
    const renderers = {
        dashboard: renderAdminDashboard,
        services: renderAdminServices,
        orders: renderAdminOrders,
        drones: renderAdminDrones,
        clients: renderAdminClients,
        settings: renderAdminSettings
    };
    renderers[section]();
}

/* ----- small formatting helpers ----- */

function formatPrice(value) {
    return `${Number(value).toFixed(2).replace('.', ',')} KM`;
}

/* Whole amount with a thousands separator for the stat cards: 1100 -> "1.100 KM" */
function formatAmount(value) {
    return `${String(Math.round(value)).replace(/\B(?=(\d{3})+(?!\d))/g, '.')} KM`;
}

/* "2026-11-05 10:00:00" -> "05.11.2026. 10:00"; "2026-11-05" -> "05.11.2026." */
function formatDateTime(value) {
    if (!value) {
        return '-';
    }
    const [datePart, timePart] = value.split(' ');
    const [year, month, day] = datePart.split('-');
    const date = `${day}.${month}.${year}.`;
    return timePart ? `${date} ${timePart.slice(0, 5)}` : date;
}

function createBadge(label) {
    return createElement('span', `badge rounded-pill bg-${label.color}-subtle text-${label.color}-emphasis`, label.text);
}

/* Builds one table row; each cell is text or a ready DOM element. */
function createTableRow(cells, alignEndIndexes = []) {
    const row = document.createElement('tr');
    cells.forEach(function (cell, index) {
        const td = createElement('td', alignEndIndexes.includes(index) ? 'text-end' : '');
        if (cell instanceof Node) {
            td.append(cell);
        } else {
            td.textContent = cell === null || cell === undefined || cell === '' ? '-' : String(cell);
        }
        row.append(td);
    });
    return row;
}

function fillTable(tbodyId, rows, columnCount) {
    const tbody = document.getElementById(tbodyId);
    tbody.replaceChildren();
    if (rows.length === 0) {
        const emptyRow = document.createElement('tr');
        const cell = createElement('td', 'text-center text-muted py-4', 'Nema podataka.');
        cell.colSpan = columnCount;
        emptyRow.append(cell);
        tbody.append(emptyRow);
        return;
    }
    tbody.append(...rows);
}

/* id -> object maps, so orders can show the client and service name instead of ids */
function indexById(items) {
    const map = {};
    items.forEach(function (item) {
        map[item.id] = item;
    });
    return map;
}

function fullName(user) {
    return user ? `${user.first_name} ${user.last_name}` : '-';
}

/* ----- sections ----- */

function renderAdminDashboard() {
    const { orders, users, drones, services, roles } = adminData;
    const usersById = indexById(users);
    const servicesById = indexById(services);
    const userRole = roles.find(function (role) {
        return role.name === 'User';
    });
    const clients = users.filter(function (user) {
        return userRole && user.role_id === userRole.id;
    });

    const activeOrders = orders.filter(function (order) {
        return ['pending', 'confirmed', 'in_progress'].includes(order.status);
    });
    const revenue = orders
        .filter(function (order) {
            return order.status === 'completed';
        })
        .reduce(function (sum, order) {
            return sum + Number(order.total_price);
        }, 0);

    document.getElementById('statOrders').textContent = orders.length;
    document.getElementById('statOrdersHint').textContent = `${activeOrders.length} aktivnih`;
    document.getElementById('statRevenue').textContent = formatAmount(revenue);
    document.getElementById('statClients').textContent = clients.length;
    document.getElementById('statClientsHint').textContent =
        `${clients.filter(function (user) { return user.is_active; }).length} aktivnih naloga`;
    document.getElementById('statDrones').textContent = drones.length;
    document.getElementById('statDronesHint').textContent =
        `${drones.filter(function (drone) { return drone.status === 'available'; }).length} dostupno`;

    // Five orders with the latest scheduled date.
    const latestOrders = orders
        .slice()
        .sort(function (a, b) {
            return String(b.scheduled_date).localeCompare(String(a.scheduled_date));
        })
        .slice(0, 5);
    fillTable('recentOrdersBody', latestOrders.map(function (order) {
        return createTableRow([
            fullName(usersById[order.user_id]),
            servicesById[order.service_id] ? servicesById[order.service_id].name : '-',
            createBadge(ORDER_STATUS_LABELS[order.status]),
            formatDateTime(order.scheduled_date)
        ]);
    }), 4);

    // Orders per status with a bar showing the share.
    const list = document.getElementById('orderStatusList');
    list.replaceChildren();
    Object.entries(ORDER_STATUS_LABELS).forEach(function ([status, label]) {
        const count = orders.filter(function (order) {
            return order.status === status;
        }).length;
        const percent = orders.length ? Math.round((count / orders.length) * 100) : 0;

        const item = createElement('li', '');
        const header = createElement('div', 'd-flex justify-content-between small mb-1');
        header.append(createElement('span', '', label.text), createElement('strong', '', String(count)));
        const bar = createElement('div', 'progress status-progress');
        const fill = createElement('div', `progress-bar bg-${label.color}`);
        fill.style.width = `${percent}%`;
        bar.append(fill);
        item.append(header, bar);
        list.append(item);
    });
}

function renderAdminServices() {
    const { services, orders } = adminData;
    fillTable('servicesBody', services.map(function (service) {
        const orderCount = orders.filter(function (order) {
            return order.service_id === service.id;
        }).length;
        return createTableRow([
            createElement('strong', '', service.name),
            service.description,
            formatPrice(service.price),
            `${service.duration_days} ${service.duration_days === 1 ? 'dan' : 'dana'}`,
            orderCount,
            createBadge(service.active
                ? { text: 'Aktivna', color: 'success' }
                : { text: 'Neaktivna', color: 'secondary' })
        ], [2]);
    }), 6);
}

function renderAdminOrders() {
    const { orders, users, services } = adminData;
    const usersById = indexById(users);
    const servicesById = indexById(services);
    const filter = document.getElementById('orderStatusFilter').value;

    const visibleOrders = orders
        .filter(function (order) {
            return filter === '' || order.status === filter;
        })
        .sort(function (a, b) {
            return String(b.scheduled_date).localeCompare(String(a.scheduled_date));
        });

    document.getElementById('ordersCount').textContent =
        `Prikazano ${visibleOrders.length} od ${orders.length} narudžbi`;

    fillTable('ordersBody', visibleOrders.map(function (order) {
        return createTableRow([
            createElement('span', 'font-monospace small', order.order_number),
            order.title,
            fullName(usersById[order.user_id]),
            servicesById[order.service_id] ? servicesById[order.service_id].name : '-',
            order.location,
            formatDateTime(order.scheduled_date),
            createBadge(ORDER_STATUS_LABELS[order.status]),
            formatPrice(order.total_price)
        ], [7]);
    }), 8);
}

function renderAdminDrones() {
    fillTable('dronesBody', adminData.drones.map(function (drone) {
        // Battery as a small bar; its width is set from JS (no inline style in the HTML view).
        let battery = '-';
        if (drone.battery_level !== null) {
            battery = createElement('div', 'd-flex align-items-center gap-2');
            const bar = createElement('div', 'progress battery-progress');
            const color = drone.battery_level >= 60 ? 'success' : (drone.battery_level >= 30 ? 'warning' : 'danger');
            const fill = createElement('div', `progress-bar bg-${color}`);
            fill.style.width = `${drone.battery_level}%`;
            bar.append(fill);
            battery.append(bar, createElement('small', 'text-muted', `${drone.battery_level}%`));
        }

        return createTableRow([
            createElement('strong', '', `${drone.brand} ${drone.model}`),
            drone.category,
            createElement('span', 'font-monospace small', drone.serial_number),
            createBadge(DRONE_STATUS_LABELS[drone.status]),
            battery,
            formatDateTime(drone.last_service_date)
        ]);
    }), 6);
}

function renderAdminClients() {
    const { users, roles, orders } = adminData;
    const rolesById = indexById(roles);
    fillTable('clientsBody', users.map(function (user) {
        const orderCount = orders.filter(function (order) {
            return order.user_id === user.id;
        }).length;
        const role = rolesById[user.role_id];
        return createTableRow([
            createElement('strong', '', fullName(user)),
            user.email,
            user.phone,
            createBadge(role && role.name === 'Admin'
                ? { text: 'Admin', color: 'primary' }
                : { text: 'Klijent', color: 'secondary' }),
            orderCount,
            createBadge(user.is_active
                ? { text: 'Aktivan', color: 'success' }
                : { text: 'Deaktiviran', color: 'danger' })
        ]);
    }), 6);
}

function renderAdminSettings() {
    fillTable('settingsBody', adminData.settings.map(function (setting) {
        return createTableRow([
            createElement('span', 'font-monospace small', setting.key_name),
            createElement('strong', '', setting.value_text || '-'),
            setting.description
        ]);
    }), 3);
}

/* ---------- Event handlers ---------- */

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
    } else if (form.id === 'weatherForm') {
        event.preventDefault();
        loadWeather(form.querySelector('#weatherCity').value);
    }
});

/*
 * One click handler for the whole document (navbar is outside #app).
 * - "Odjavi se" logs out.
 * - Section links (data-section="usluge" or href="#usluge") scroll without changing the route hash.
 * - Clicking the link of the page you are already on scrolls to the top.
 * - Any click on a navbar link closes the mobile menu.
 */
document.addEventListener('click', function (event) {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) {
        return;
    }

    if (target.closest('#logoutBtn')) {
        closeMobileMenu();
        logout();
        return;
    }

    if (target.closest('[data-action="weather-retry"]')) {
        const cityInput = document.getElementById('weatherCity');
        loadWeather(cityInput ? cityInput.value : '');
        return;
    }

    if (target.closest('[data-action="admin-retry"]')) {
        initAdminPanel();
        return;
    }

    const link = target.closest('a[href^="#"]');
    if (!link) {
        return;
    }

    const href = link.getAttribute('href');
    const isRouteLink = href.startsWith('#/');
    const sectionId = link.dataset.section || (isRouteLink ? null : href.slice(1));

    if (sectionId !== null) {
        // "#usluge" or data-section: never let the browser change the hash.
        event.preventDefault();
        if (getCurrentRoute() === 'home') {
            scrollToSection(sectionId);
            if (link.closest('#mainNavbar')) {
                activateNavLink(link);
            }
        } else if (link.dataset.section) {
            pendingSection = sectionId;
            window.location.hash = '#/home';
        }
    } else if (isRouteLink && href === `#/${getCurrentRoute()}`) {
        // Same page: hashchange will not fire, so just go to the top and reset the active link.
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        updateNavigation(getCurrentRoute());
    }

    if (link.closest('#mainNavbar')) {
        closeMobileMenu();
    }
});

/* Admin orders: the status filter re-renders the table from the data already loaded. */
app.addEventListener('change', function (event) {
    if (event.target.id === 'orderStatusFilter' && adminData) {
        renderAdminOrders();
    }
});

window.addEventListener('hashchange', function () {
    if (window.location.hash.startsWith('#/')) {
        renderRoute();
    }
});

renderRoute();
