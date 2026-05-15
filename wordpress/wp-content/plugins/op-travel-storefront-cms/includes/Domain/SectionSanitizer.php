<?php

namespace OPTravelStorefrontCMS\Domain;

final class SectionSanitizer
{
    public static function sanitizeMany($sections)
    {
        if (! is_array($sections)) {
            return [];
        }

        $normalized = [];

        foreach ($sections as $section) {
            $sanitized = self::sanitizeOne($section);

            if ($sanitized === null) {
                continue;
            }

            $normalized[] = $sanitized;
        }

        return $normalized;
    }

    public static function sanitizeOne($section)
    {
        if (! is_array($section)) {
            return null;
        }

        $type = self::sanitizeText($section['type'] ?? '');

        if ($type === '') {
            return null;
        }

        return [
            'id' => self::sanitizeIdentifier($section['id'] ?? self::generateIdentifier()),
            'type' => $type,
            'label' => self::sanitizeText($section['label'] ?? ''),
            'enabled' => self::toBool($section['enabled'] ?? false),
            'settings' => self::sanitizeDictionary($section['settings'] ?? []),
            'content' => self::sanitizeDictionary($section['content'] ?? []),
            'bindings' => self::sanitizeDictionary($section['bindings'] ?? []),
        ];
    }

    private static function sanitizeDictionary($value)
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalizedKey = self::sanitizeIdentifier($key);

            if ($normalizedKey === '') {
                continue;
            }

            $normalized[$normalizedKey] = self::sanitizeValue($item);
        }

        return $normalized;
    }

    private static function sanitizeValue($value)
    {
        if (is_array($value)) {
            return self::sanitizeDictionary($value);
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return self::sanitizeText($value);
    }

    private static function sanitizeIdentifier($value)
    {
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value);
        $value = trim((string) $value, '-');

        return $value;
    }

    private static function sanitizeText($value)
    {
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field((string) $value);
        }

        $value = trim((string) $value);
        $value = strip_tags($value);

        return preg_replace('/\s+/', ' ', $value);
    }

    private static function toBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private static function generateIdentifier()
    {
        $seed = uniqid('section-', true);
        $seed = str_replace('.', '-', $seed);

        return self::sanitizeIdentifier($seed);
    }
}
