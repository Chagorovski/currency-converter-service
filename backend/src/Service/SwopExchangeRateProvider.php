<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\SwopApiException;
use InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SwopExchangeRateProvider implements ExchangeRateProviderInterface
{
    public function __construct(
        private HttpClientInterface $http,
        private string $baseUrl,
        private string $apiKey,
        private CacheInterface $cache,
        private int $ttlSeconds = 3600,
    ) {}

    /** Return the EUR→{currency} spot rate, cached. */
    public function getEurToQuote(string $currencyCode): float
    {
        $currencyCode = $this->normalizeCurrencyCode($currencyCode);
        $cacheKey = $this->buildCacheKey('rate', 'EUR', $currencyCode); // e.g. rate.EUR.USD

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($currencyCode) {
            $item->expiresAfter($this->ttlSeconds);

            $response = $this->http->request('GET', rtrim($this->baseUrl, '/') . '/rates', [
                'headers' => [
                    'Authorization' => 'ApiKey ' . $this->apiKey,
                    'Accept'        => 'application/json',
                ],
                'query' => [
                    'base_currency'  => 'EUR',
                    'quote_currency' => $currencyCode,
                ],
                'timeout' => 3.0,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw SwopApiException::fromHttpStatus($response->getStatusCode());
            }

            $payload = $response->toArray(false);
            return $this->parseQuoteFromPayload($payload, $currencyCode);
        });
    }

    private function normalizeCurrencyCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            throw new InvalidArgumentException('Invalid currency code: ' . $code);
        }
        return $code;
    }

    /** Symfony Cache safe key (avoid reserved characters). */
    private function buildCacheKey(string ...$parts): string
    {
        $joined = strtoupper(implode('.', $parts));
        return (string)preg_replace('/[^A-Z0-9._-]/', '_', $joined);
    }

    /** Parse the array-of-rows Swop payload and extract the quote for the requested currency. */
    private function parseQuoteFromPayload(mixed $data, string $currencyCode): float
    {
        if (is_array($data)) {
            foreach ($data as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $qc = strtoupper((string)($row['quote_currency'] ?? ''));
                if ($qc !== $currencyCode) {
                    continue;
                }
                $val = $row['quote'] ?? $row['rate'] ?? null;
                if (is_numeric($val)) {
                    return (float)$val;
                }
            }
        }
        throw SwopApiException::missingRate($currencyCode);
    }
}
