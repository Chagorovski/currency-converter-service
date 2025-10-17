<?php

namespace App\Dto;

final class CsrfTokenResponse implements \JsonSerializable
{
    public function __construct(public string $token) {}

    public function jsonSerialize(): array
    {
        return ['token' => $this->token];
    }
}
