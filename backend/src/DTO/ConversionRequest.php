<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ConversionRequest
{
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    public float $amount;

    #[Assert\NotBlank]
    #[Assert\Length(exactly: 3)]
    #[Assert\Regex(pattern: '/^[A-Z]{3}$/')]
    public string $sourceCurrency;

    #[Assert\NotBlank]
    #[Assert\Length(exactly: 3)]
    #[Assert\Regex(pattern: '/^[A-Z]{3}$/')]
    public string $targetCurrency;

    public static function fromArray(array $input): self
    {
        $self = new self();
        $self->amount = (float)($input['amount'] ?? 0);
        $self->sourceCurrency = strtoupper((string)($input['from'] ?? ''));
        $self->targetCurrency = strtoupper((string)($input['to'] ?? ''));
        return $self;
    }
}
