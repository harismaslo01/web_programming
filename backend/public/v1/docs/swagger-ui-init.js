window.addEventListener('DOMContentLoaded', function () {
    window.ui = SwaggerUIBundle({
        url: './swagger.php',
        dom_id: '#swagger-ui',
        deepLinking: true,
        persistAuthorization: true
    });
});
