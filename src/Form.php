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
    /**
     * The characters stripped from both ends of a submitted value: ASCII
     * whitespace, plus the invisible characters a browser, an autofill or a
     * copy-paste actually delivers — no-break space, the zero-width family and
     * the byte-order mark.
     */
    private const TRIM_PATTERN = '/^[\s\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}]+|[\s\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}]+$/u';

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
     * absent, or was not submitted as a plain string free of NUL bytes.
     */
    public static function fetchGet(string $key, ?string $default = null): ?string
    {
        return self::fetchFrom($_GET, $key, $default);
    }

    /**
     * Returns the trimmed form value, or $default when the field is absent, or
     * was not submitted as a plain string free of NUL bytes.
     */
    public static function fetchPost(string $key, ?string $default = null): ?string
    {
        return self::fetchFrom($_POST, $key, $default);
    }

    /**
     * Returns the required fields as trimmed strings, or null when any of them
     * is missing or empty. A field holding "0" counts as filled.
     *
     * Success with an empty $required is `[]`, which is falsy: compare the
     * result with `=== null`, never with `if (!$data)`.
     *
     * @param list<string> $required
     *
     * @return array<string, string>|null
     */
    public static function validatePost(array $required): ?array
    {
        $values = [];

        foreach ($required as $field) {
            $value = self::fetchFilledPost($field);

            if ($value === null) {
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
            if (self::fetchFilledPost($field) === null) {
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
     * The single rule for whether a required field was filled in, so
     * validatePost() and findMissingPost() cannot drift apart.
     */
    private static function fetchFilledPost(string $field): ?string
    {
        $value = self::fetchPost($field);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<array-key, mixed> $source
     */
    private static function fetchFrom(array $source, string $key, ?string $default): ?string
    {
        $value = $source[$key] ?? null;

        // A NUL byte truncates the value in everything downstream that is
        // written in C — filesystem calls, several database drivers, header
        // output — so "admin\x00.txt" is not the string it looks like. Treat it
        // as unusable input, exactly like an array submission.
        if (!is_string($value) || str_contains($value, "\x00")) {
            return $default;
        }

        return self::trimBlank($value);
    }

    /**
     * trim() works on bytes, so a field holding nothing but a no-break space or
     * a zero-width space passed a required check while looking empty.
     */
    private static function trimBlank(string $value): string
    {
        $trimmed = preg_replace(self::TRIM_PATTERN, '', $value);

        // The subject is whatever the client sent, so it need not be valid
        // UTF-8; fall back to the byte-based trim rather than to no trim.
        return $trimmed ?? trim($value);
    }

    private static function readMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;

        // Compared exactly: HTTP methods are case-sensitive, and a server that
        // reports "post" is not one this code should be guessing for.
        return is_string($method) ? $method : '';
    }
}
