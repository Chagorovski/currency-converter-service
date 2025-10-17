<?php

namespace App\Dto;

final class LoginResponse implements \JsonSerializable
{
    public function __construct(
        public bool $ok,
        public ?string $user = null
    ) {}

    public function jsonSerialize(): array
    {
        return ['ok' => $this->ok, 'user' => $this->user];
    }
}
