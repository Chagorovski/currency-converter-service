<?php

namespace App\Util;

use InvalidArgumentException;

final class FormatHelper
{
    private function __construct() {}

    public static function normalizeCurrencyCode(string $code): string
    {
        $currencyCode = strtoupper(trim($code));
        if (preg_match('/^[A-Z]{3}$/', $currencyCode) !== 1) {
            throw new InvalidArgumentException('Invalid currency code: ' . $currencyCode);
        }

        return $currencyCode;
    }

    public static function normalizeLocale(string $acceptLanguage): string
    {
        $first = explode(',', $acceptLanguage, 2)[0] ?? 'en-US';
        $first = explode(';', $first, 2)[0] ?? 'en-US';
        $first = trim($first) ?: 'en-US';
        $replace = str_replace('-', '_', $first);
        if ($replace === '' || preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $replace) !== 1) {
            return 'en_US';
        }
        return $replace;
    }

    public static function cacheKey(string ...$parts): string
    {
        $joined = strtoupper(implode('.', $parts));
        return (string) preg_replace('/[^A-Z0-9._-]/', '_', $joined);
    }
}
