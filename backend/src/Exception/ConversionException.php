<?php
declare(strict_types=1);

namespace App\Exception;

/**
 * Thrown for logical conversion/calculation issues.
 */
final class ConversionException extends \RuntimeException
{
    public static function invalidRates(float $from, float $to): self
    {
        return new self(sprintf('Invalid rate(s): EUR→FROM=%.4f EUR→TO=%.4f', $from, $to));
    }
}
