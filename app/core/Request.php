<?php

namespace App\Core;

use App\Core\Libraries\Parser;

class Request
{
    private array $input = [];
    private ?string $contentType = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? 'application/json';
        $this->parseInput();
    }

    /**
     * Parse input based on content type
     */
    private function parseInput(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            $this->input = $_GET;
        } elseif ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
            $body = file_get_contents('php://input');

            if (!empty($body)) {
                try {
                    $this->input = Parser::parseByContentType($body, $this->contentType);
                } catch (\Exception $e) {
                    Logger::warning("Failed to parse input: " . $e->getMessage());
                    $this->input = [];
                }
            } elseif ($method === 'POST') {
                $this->input = $_POST;
            }
        }
    }

    /**
     * Get input value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input[$key] ?? $default;
    }

    /**
     * Get all input
     */
    public function all(): array
    {
        return $this->input;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return isset($this->input[$key]);
    }

    /**
     * Get HTTP method
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get content type
     */
    public function contentType(): string
    {
        return $this->contentType;
    }

    /**
     * Get request URI
     */
    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get request path (without query string)
     */
    public function path(): string
    {
        return parse_url($this->uri(), PHP_URL_PATH) ?? '/';
    }
}
