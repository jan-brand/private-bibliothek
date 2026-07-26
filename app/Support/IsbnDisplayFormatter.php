<?php

namespace App\Support;

use Nicebooks\Isbn\Isbn;
use Throwable;

class IsbnDisplayFormatter
{
    public static function normalize(string $value): string
    {
        $normalized = preg_replace(
            '/[^0-9A-Za-z]+/',
            '',
            trim($value),
        );

        return strtoupper($normalized ?? '');
    }

    public static function isCandidate(string $value): bool
    {
        $normalized = self::normalize($value);

        return self::isIsbn10Shape($normalized)
            || self::isIsbn13Shape($normalized);
    }

    public static function normalizeBarcode(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return self::normalize($value);
    }

    public static function format(?string $value): string
    {
        $original = trim((string) $value);

        if ($original === '') {
            return '';
        }

        $normalized = self::normalize($original);

        if (! self::isCandidate($normalized)) {
            return $original;
        }

        try {
            $candidate = self::withValidCheckDigit($normalized);
            $formatted = Isbn::of($candidate)->toFormattedString();

            if (! str_contains($formatted, '-')) {
                return $normalized;
            }

            return substr($formatted, 0, -1).substr($normalized, -1);
        } catch (Throwable) {
            return $normalized;
        }
    }

    private static function withValidCheckDigit(string $normalized): string
    {
        if (self::isIsbn13Shape($normalized)) {
            $sum = 0;

            for ($index = 0; $index < 12; $index++) {
                $sum += (int) $normalized[$index]
                    * ($index % 2 === 0 ? 1 : 3);
            }

            $checkDigit = (10 - ($sum % 10)) % 10;

            return substr($normalized, 0, 12).$checkDigit;
        }

        $sum = 0;

        for ($index = 0; $index < 9; $index++) {
            $sum += (int) $normalized[$index] * (10 - $index);
        }

        $checkDigit = (11 - ($sum % 11)) % 11;

        return substr($normalized, 0, 9).match ($checkDigit) {
            10 => 'X',
            default => (string) $checkDigit,
        };
    }

    private static function isIsbn10Shape(string $normalized): bool
    {
        return strlen($normalized) === 10
            && ctype_digit(substr($normalized, 0, 9))
            && (
                ctype_digit($normalized[9])
                || $normalized[9] === 'X'
            );
    }

    private static function isIsbn13Shape(string $normalized): bool
    {
        return strlen($normalized) === 13
            && ctype_digit($normalized);
    }
}
