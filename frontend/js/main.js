document.addEventListener('DOMContentLoaded', function () {
    const role = (() => {
        try {
            const saved = JSON.parse(localStorage.getItem('dronepilot_user') || '{}');
            return saved.role || 'guest';
        } catch (error) {
            return 'guest';
        }
    })();

    const navOrders = document.getElementById('navOrders');
    const navProfile = document.getElementById('navProfile');
    const authActions = document.getElementById('authActions');

    if (navOrders) {
        navOrders.style.display = role === 'admin' ? '' : 'none';
    }

    if (navProfile) {
        navProfile.style.display = role === 'guest' ? 'none' : '';
    }

    if (authActions) {
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        const userBadge = document.getElementById('userBadge');

        if (role === 'guest') {
            if (loginBtn) loginBtn.style.display = '';
            if (registerBtn) registerBtn.style.display = '';
            if (logoutBtn) logoutBtn.style.display = 'none';
            if (userBadge) userBadge.style.display = 'none';
        } else {
            if (loginBtn) loginBtn.style.display = 'none';
            if (registerBtn) registerBtn.style.display = 'none';
            if (logoutBtn) logoutBtn.style.display = '';
            if (userBadge) {
                userBadge.style.display = 'inline-flex';
                userBadge.textContent = role === 'admin' ? 'Admin' : 'User';
            }
        }
    }

    const logoutButton = document.getElementById('logoutBtn');
    if (logoutButton) {
        logoutButton.addEventListener('click', function () {
            localStorage.removeItem('dronepilot_user');
            window.location.href = 'index.html';
        });
    }

    const loginModalBtn = document.getElementById('loginBtn');
    if (loginModalBtn) {
        loginModalBtn.addEventListener('click', function () {
            window.location.href = 'login.html';
        });
    }

    const registerModalBtn = document.getElementById('registerBtn');
    if (registerModalBtn) {
        registerModalBtn.addEventListener('click', function () {
            window.location.href = 'register.html';
        });
    }
});