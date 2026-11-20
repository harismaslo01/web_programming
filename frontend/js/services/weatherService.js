/*
 * Frontend service for the weather endpoints of our backend.
 * The backend calls the external Open-Meteo API (backend/services/WeatherService.php),
 * so the browser never talks to Open-Meteo directly.
 */
const WeatherService = {
    /* GET /backend/weather/flight-conditions?city=... -> current weather + flight verdict */
    getFlightConditions(city) {
        return apiGet('weather/flight-conditions', { city });
    },

    /* GET /backend/weather/forecast?city=...&days=... -> daily forecast + verdict per day */
    getForecast(city, days = 5) {
        return apiGet('weather/forecast', { city, days });
    }
};
