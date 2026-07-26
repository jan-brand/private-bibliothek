<?php

namespace App\Support;

class BarcodeNormalizer
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
}
