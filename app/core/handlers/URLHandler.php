<?php

namespace App\Core\Handlers;

class URLHandler
{
    /**
     * Build URL
     */
    public static function build(string $path, array $queryParams = []): string
    {
        $url = $path;

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * Parse current URL
     */
    public static function parse(): array
    {
        $uri = parse_url($_SERVER['REQUEST_URI']);
        $path = $uri['path'] ?? '/';
        $queryString = $uri['query'] ?? '';

        parse_str($queryString, $query);

        return [
            'path' => $path,
            'query' => $query,
            'method' => $_SERVER['REQUEST_METHOD'],
            'host' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        ];
    }

    /**
     * Get route from path
     */
    public static function getRoute(): string
    {
        $parsed = self::parse();
        return $parsed['path'];
    }

    /**
     * Get query parameter
     */
    public static function getQueryParam(string $key, mixed $default = null): mixed
    {
        $parsed = self::parse();
        return $parsed['query'][$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public static function getQueryParams(): array
    {
        $parsed = self::parse();
        return $parsed['query'];
    }

    /**
     * Get HTTP method
     */
    public static function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $code = 302): void
    {
        header("Location: $url", true, $code);
        exit;
    }

    /**
     * Get base URL
     */
    public static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host";
    }
}
