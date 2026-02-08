<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];

    /**
     * Set status code
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set header
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send JSON response
     */
    public function json(mixed $data): void
    {
        $this->header('Content-Type', 'application/json');
        $this->send(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Send XML response
     */
    public function xml(mixed $data): void
    {
        $this->header('Content-Type', 'application/xml');
        $this->send($this->arrayToXml($data));
    }

    /**
     * Send HTML response
     */
    public function html(string $content): void
    {
        $this->header('Content-Type', 'text/html; charset=utf-8');
        $this->send($content);
    }

    /**
     * Send plain text response
     */
    public function text(string $content): void
    {
        $this->header('Content-Type', 'text/plain; charset=utf-8');
        $this->send($content);
    }

    /**
     * Send response
     */
    private function send(string $content): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $content;
        exit;
    }

    /**
     * Convert array to XML
     */
    private function arrayToXml(mixed $data, string $root = 'root'): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<{$root}>\n";
        $xml .= $this->buildXml($data);
        $xml .= "</{$root}>";
        return $xml;
    }

    /**
     * Build XML recursively
     */
    private function buildXml(mixed $data): string
    {
        $xml = '';

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $key = is_numeric($key) ? 'item' : $key;
                if (is_array($value)) {
                    $xml .= "<{$key}>\n";
                    $xml .= $this->buildXml($value);
                    $xml .= "</{$key}>\n";
                } else {
                    $xml .= "<{$key}>" . htmlspecialchars((string)$value) . "</{$key}>\n";
                }
            }
        } else {
            $xml .= htmlspecialchars((string)$data);
        }

        return $xml;
    }
}
