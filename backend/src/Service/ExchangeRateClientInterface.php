<?php

namespace App\Service;

interface ExchangeRateClientInterface
{
    public function fetchExchangeCurrency(string $quoteCurrency): float;
}
