/*
 * Shared AJAX helper for the DronePilot REST API (backend/).
 * Views never call fetch() for data; frontend service files (weatherService.js, ...) use apiGet().
 * Milestone 4 extends this file with POST/PUT/DELETE and the JWT header.
 */
const API_BASE_URL = 'backend/';
// The backend may call Open-Meteo twice (city lookup + forecast) with a 5 s timeout each.
const API_TIMEOUT_MS = 15000;

class ApiError extends Error {
    constructor(message, status) {
        super(message);
        this.name = 'ApiError';
        // 0 = no answer (network error or timeout), otherwise the HTTP status code.
        this.status = status;
    }
}

async function apiGet(path, params = {}) {
    const url = new URL(path, new URL(API_BASE_URL, window.location.href));
    Object.entries(params).forEach(function ([key, value]) {
        url.searchParams.set(key, value);
    });

    // Abort the request when the server does not answer in time.
    const controller = new AbortController();
    const timer = setTimeout(function () {
        controller.abort();
    }, API_TIMEOUT_MS);

    let response;
    try {
        response = await fetch(url, {
            headers: { Accept: 'application/json' },
            signal: controller.signal
        });
    } catch (error) {
        throw new ApiError(error.name === 'AbortError' ? 'Request timed out.' : 'Network error.', 0);
    } finally {
        clearTimeout(timer);
    }

    let data = null;
    try {
        data = await response.json();
    } catch (error) {
        data = null;
    }

    if (!response.ok) {
        // The API always answers errors as {"error": "..."}.
        throw new ApiError(data && data.error ? data.error : `HTTP ${response.status}`, response.status);
    }
    if (data === null) {
        throw new ApiError('The server did not return valid JSON.', response.status);
    }

    return data;
}
