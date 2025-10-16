<?php
declare(strict_types=1);

namespace App\Exception;

/**
 * Thrown when Swop API responds with an error or invalid data.
 */
final class SwopApiException extends \RuntimeException
{
    public static function fromHttpStatus(int $status): self
    {
        return new self(sprintf('Swop API returned HTTP %d', $status));
    }

    public static function missingRate(string $currency): self
    {
        return new self(sprintf('Missing rate for currency: %s', $currency));
    }

    public static function invalidPayload(): self
    {
        return new self('Unexpected or malformed Swop API payload.');
    }
}
