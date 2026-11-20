<?php

/*
 * An expected error with an HTTP status code, e.g. 400 (invalid input), 404 (not found),
 * 409 (conflict) or 503 (external service unavailable).
 * The global error handler in index.php returns its message to the client as JSON.
 */
class HttpException extends RuntimeException
{
    private int $statusCode;

    public function __construct(int $statusCode, string $message)
    {
        if ($statusCode < 400 || $statusCode > 599) {
            throw new InvalidArgumentException('HTTP exceptions must use a 4xx or 5xx status code.');
        }

        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
