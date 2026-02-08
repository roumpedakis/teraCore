<?php

namespace App\Core\Classes;

abstract class BaseView
{
    protected string $format = 'json';

    /**
     * Set output format
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * Render output
     */
    public function render(mixed $data): mixed
    {
        return match ($this->format) {
            'json' => $this->renderJson($data),
            'xml' => $this->renderXml($data),
            'html' => $this->renderHtml($data),
            default => $this->renderJson($data),
        };
    }

    /**
     * Render as JSON
     */
    protected function renderJson(mixed $data): string
    {
        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Render as XML
     */
    protected function renderXml(mixed $data): string
    {
        header('Content-Type: application/xml');
        return $this->arrayToXml($data, 'root');
    }

    /**
     * Render as HTML
     */
    protected function renderHtml(mixed $data): string
    {
        header('Content-Type: text/html; charset=utf-8');
        return '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
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
    private function buildXml(mixed $data, string $prefix = ''): string
    {
        $xml = '';

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $key = is_numeric($key) ? 'item' : $key;
                if (is_array($value)) {
                    $xml .= "<{$key}>\n";
                    $xml .= $this->buildXml($value, $key);
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
