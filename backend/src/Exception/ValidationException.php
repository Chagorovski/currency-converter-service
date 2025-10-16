<?php
declare(strict_types=1);

namespace App\Exception;

/**
 * Thrown when user input or request validation fails.
 */
final class ValidationException extends \InvalidArgumentException
{
    public function __construct(string $message, private array $details = [])
    {
        parent::__construct($message);
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
