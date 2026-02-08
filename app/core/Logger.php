<?php

namespace App\Core;

class Logger
{
    private static string $logPath = '';

    /**
     * Initialize logger with log path
     */
    public static function init(string $logPath = ''): void
    {
        if (empty($logPath)) {
            $logPath = dirname(__DIR__, 2) . '/storage/logs';
        }

        self::$logPath = $logPath;

        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    /**
     * Log information
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log warning
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log error
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log debug (only in debug mode)
     */
    public static function debug(string $message, array $context = []): void
    {
        if (Config::get('APP_DEBUG') === 'true' || Config::get('APP_DEBUG') === true) {
            self::log('DEBUG', $message, $context);
        }
    }

    /**
     * Write log to file
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        if (empty(self::$logPath)) {
            self::init();
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        $logFile = self::$logPath . '/' . date('Y-m-d') . '.log';

        file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Console output in debug mode
        if (Config::get('APP_DEBUG') === 'true' || Config::get('APP_DEBUG') === true) {
            echo $logMessage;
        }
    }
}
