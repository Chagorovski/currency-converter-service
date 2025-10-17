<?php

namespace App\Dto;

final class SessionResponse implements \JsonSerializable
{
    public function __construct(
        public bool $authenticated,
        public ?string $user
    ) {}

    public function jsonSerialize(): array
    {
        return ['authenticated' => $this->authenticated, 'user' => $this->user];
    }
}
