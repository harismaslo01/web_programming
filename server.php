<?php

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = rawurldecode(parse_url($requestUri, PHP_URL_PATH));
$docsUrlPrefix = '/backend/public/v1/docs';
$docsDirectory = __DIR__ . '/backend/public/v1/docs';

if ($requestPath === $docsUrlPrefix || $requestPath === $docsUrlPrefix . '/') {
    header('Content-Type: text/html; charset=utf-8');
    readfile($docsDirectory . '/index.html');
    return true;
}

if ($requestPath === $docsUrlPrefix . '/swagger.php') {
    require $docsDirectory . '/swagger.php';
    return true;
}

if (str_starts_with($requestPath, $docsUrlPrefix . '/')) {
    $relativePath = substr($requestPath, strlen($docsUrlPrefix) + 1);
    $realDocsDirectory = realpath($docsDirectory);
    $requestedFile = $realDocsDirectory === false
        ? false
        : realpath(
            $realDocsDirectory . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath)
        );

    if ($requestedFile !== false
        && str_starts_with(
            strtolower($requestedFile),
            strtolower($realDocsDirectory . DIRECTORY_SEPARATOR)
        )) {
        return false;
    }

    http_response_code(404);
    echo 'Not Found';
    return true;
}

if ($requestPath === '/backend' || str_starts_with($requestPath, '/backend/')) {
    $apiRequestUri = substr($requestUri, strlen('/backend'));
    if ($apiRequestUri === '' || $apiRequestUri[0] === '?') {
        $apiRequestUri = '/' . $apiRequestUri;
    }
    $_SERVER['REQUEST_URI'] = $apiRequestUri;

    require __DIR__ . '/backend/index.php';
    return true;
}

return false;
