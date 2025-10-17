<?php

namespace App\Dto;

final class ConversionResponse implements \JsonSerializable
{
    public function __construct(
        public float $amount,
        public string $from,
        public string $to,
        public float $rate,
        public float $converted,
        public string $formatted,
        public string $source,
        public string $timestamp
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'from' => $this->from,
            'to' => $this->to,
            'rate' => $this->rate,
            'converted' => $this->converted,
            'formatted' => $this->formatted,
            'source' => $this->source,
            'timestamp' => $this->timestamp,
        ];
    }
}
