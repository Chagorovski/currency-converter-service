<?php
declare(strict_types=1);

namespace App\Service;

interface ExchangeRateProviderInterface
{
    public function getEurToQuote(string $currencyCode): float;
}
