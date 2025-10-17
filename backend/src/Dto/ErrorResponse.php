<?php

namespace App\Dto;

final class ErrorResponse implements \JsonSerializable
{
    public function __construct(public string $error) {}

    public function jsonSerialize(): array
    {
        return ['error' => $this->error];
    }
}
