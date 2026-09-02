<?php

declare(strict_types=1);

namespace UmitYatarkalkmaz;

/**
 * Reads request input as trimmed strings and escapes values on the way out.
 *
 * Input is never escaped when it is read: escaping belongs to the context the
 * value is written to, and applying it here would both corrupt stored data and
 * give no protection at all outside HTML.
 */
final class Form
{
    private function __construct()
    {
    }

    public static function isPost(): bool
    {
        return self::readMethod() === 'POST';
    }

    public static function isGet(): bool
    {
        return self::readMethod() === 'GET';
    }

    /**
     * Returns the trimmed query-string value, or $default when the field is
     * absent or was not submitted as a plain string.
     */
    public static function fetchGet(string $key, ?string $default = null): ?string
    {
        return self::fetchFrom($_GET, $key, $default);
    }

    /**
     * Returns the trimmed form value, or $default when the field is absent or
     * was not submitted as a plain string.
     */
    public static function fetchPost(string $key, ?string $default = null): ?string
    {
        return self::fetchFrom($_POST, $key, $default);
    }

    /**
     * Returns the required fields as trimmed strings, or null when any of them
     * is missing or empty. A field holding "0" counts as filled.
     *
     * @param list<string> $required
     *
     * @return array<string, string>|null
     */
    public static function validatePost(array $required): ?array
    {
        $values = [];

        foreach ($required as $field) {
            $value = self::fetchPost($field);

            if ($value === null || $value === '') {
                return null;
            }

            $values[$field] = $value;
        }

        return $values;
    }

    /**
     * Names the required fields that were missing or empty, so the caller can
     * tell the visitor which ones to fill in.
     *
     * @param list<string> $required
     *
     * @return list<string>
     */
    public static function findMissingPost(array $required): array
    {
        $missing = [];

        foreach ($required as $field) {
            $value = self::fetchPost($field);

            if ($value === null || $value === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Escapes a value for output inside HTML text or a quoted attribute.
     *
     * Call this where the value is printed, not where it is read.
     */
    public static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<array-key, mixed> $source
     */
    private static function fetchFrom(array $source, string $key, ?string $default): ?string
    {
        $value = $source[$key] ?? null;

        if (!is_string($value)) {
            return $default;
        }

        return trim($value);
    }

    private static function readMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;

        return is_string($method) ? strtoupper($method) : '';
    }
}
