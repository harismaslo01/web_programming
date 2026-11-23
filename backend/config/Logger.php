<?php

/*
 * Minimal file logger.
 * info()  -> backend/logs/info.log  (e.g. external API calls)
 * error() -> backend/logs/error.log (e.g. unhandled exceptions, external API failures)
 * Milestone 4 reuses this class for request logging, logins and 401/403 entries.
 */
class Logger
{
    private const LOG_DIRECTORY = __DIR__ . '/../logs';

    public static function info(string $message): void
    {
        self::write('info.log', 'INFO', $message);
    }

    public static function error(string $message): void
    {
        self::write('error.log', 'ERROR', $message);
    }

    private static function write(string $fileName, string $level, string $message): void
    {
        if (!is_dir(self::LOG_DIRECTORY)) {
            @mkdir(self::LOG_DIRECTORY, 0775, true);
        }

        // Example line: [2026-11-21 14:05:12] INFO GET /weather 200 ...
        $line = sprintf('[%s] %s %s', date('Y-m-d H:i:s'), $level, $message) . PHP_EOL;

        // Logging must never break the API, so write errors are silenced.
        @file_put_contents(self::LOG_DIRECTORY . '/' . $fileName, $line, FILE_APPEND | LOCK_EX);
    }
}
