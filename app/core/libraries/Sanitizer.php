<?php

namespace App\Core\Libraries;

class Sanitizer
{
    /**
     * Sanitize string
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail(string $email): string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return $email ?: '';
    }

    /**
     * Sanitize integer
     */
    public static function sanitizeInt(mixed $input): int
    {
        return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float
     */
    public static function sanitizeFloat(mixed $input): float
    {
        return (float)filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize URL
     */
    public static function sanitizeUrl(string $url): string
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return $url ?: '';
    }

    /**
     * Strip tags
     */
    public static function stripTags(string $input, string $allowed = ''): string
    {
        return strip_tags($input, $allowed);
    }

    /**
     * Escape HTML
     */
    public static function escapeHtml(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate integer
     */
    public static function validateInt(mixed $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate float
     */
    public static function validateFloat(mixed $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Validate required (not empty)
     */
    public static function validateRequired(mixed $input): bool
    {
        return !empty($input);
    }

    /**
     * Validate min length
     */
    public static function validateMinLength(string $input, int $min): bool
    {
        return strlen($input) >= $min;
    }

    /**
     * Validate max length
     */
    public static function validateMaxLength(string $input, int $max): bool
    {
        return strlen($input) <= $max;
    }

    /**
     * Validate regex pattern
     */
    public static function validatePattern(string $input, string $pattern): bool
    {
        return preg_match($pattern, $input) > 0;
    }
}
