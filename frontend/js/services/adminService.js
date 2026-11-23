/*
 * Frontend service for the admin panel. It reads every entity from the REST API.
 * Views never call the API directly; main.js asks this service and renders the result.
 * Creating, editing and deleting records from the admin panel comes in Milestone 4 (JWT).
 */
const AdminService = {
    getServices() {
        return apiGet('services');
    },

    getOrders() {
        return apiGet('orders');
    },

    getDrones() {
        return apiGet('drones');
    },

    getUsers() {
        return apiGet('users');
    },

    getRoles() {
        return apiGet('roles');
    },

    getSettings() {
        return apiGet('settings');
    },

    /* Loads all admin data at once (the lists are small), so every section can use it. */
    async loadAll() {
        const [services, orders, drones, users, roles, settings] = await Promise.all([
            this.getServices(),
            this.getOrders(),
            this.getDrones(),
            this.getUsers(),
            this.getRoles(),
            this.getSettings()
        ]);

        return { services, orders, drones, users, roles, settings };
    }
};
