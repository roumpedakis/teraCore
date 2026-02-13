<?php

namespace App\Core\Libraries;

class Parser
{
    /**
     * Parse JSON
     */
    public static function parseJson(string $input): array
    {
        $decoded = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        return $decoded ?? [];
    }

    /**
     * Parse XML
     */
    public static function parseXml(string $input): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NONET);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            $message = 'Invalid XML';
            if (!empty($errors)) {
                $message = trim($errors[0]->message);
            }

            throw new \InvalidArgumentException('XML parsing error: ' . $message);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return json_decode(json_encode($xml), true);
    }

    /**
     * Parse form data (query string)
     */
    public static function parseFormData(string $input): array
    {
        parse_str($input, $output);
        return $output ?? [];
    }

    /**
     * Auto-detect and parse based on content type
     */
    public static function parseByContentType(string $input, string $contentType): array
    {
        return match ($contentType) {
            'application/json' => self::parseJson($input),
            'application/xml', 'text/xml' => self::parseXml($input),
            'application/x-www-form-urlencoded' => self::parseFormData($input),
            default => self::parseJson($input), // Default to JSON
        };
    }
}
