<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\SwopApiException;
use App\Util\FormatHelper;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SwopExchangeRateProvider implements ExchangeRateProviderInterface
{
    public function __construct(
        private ExchangeRateClientInterface $clientApi,
        private CacheInterface $cache,
        private int $ttlSeconds = 3600,
    ) {}

    public function fetchExchangeRateCurrency(string $currencyCode): float
    {
        $code = FormatHelper::normalizeCurrencyCode($currencyCode);
        $cacheKey = FormatHelper::cacheKey('rate', 'EUR', $code);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($code) {
            $item->expiresAfter($this->ttlSeconds);
            $rate = $this->clientApi->fetchExchangeCurrency($code);
            if ($rate <= 0.0) {
                throw SwopApiException::missingRate($code);
            }

            return $rate;
        });
    }
}
