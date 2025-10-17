<?php

namespace App\Service;

use App\Exception\SwopApiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SwopApiClient implements ExchangeRateClientInterface
{
    private const RATES_ENDPOINT = '/rates';

    public function __construct(
        private HttpClientInterface $http,
        private string              $baseUrl,
        private string              $apiKey,
        private float               $timeoutSeconds = 3.0,
    ){}

    public function fetchExchangeCurrency(string $quoteCurrency): float
    {
        $url = rtrim($this->baseUrl, '/') . self::RATES_ENDPOINT;
        $response = $this->http->request('GET', $url,
            ['headers' => [
                'Authorization' => 'ApiKey ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
            'query' => [
                'base_currency' => 'EUR',
                'quote_currency' => $quoteCurrency,
            ],
            'timeout' => $this->timeoutSeconds,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw SwopApiException::fromHttpStatus($response->getStatusCode());
        }

        $data = $response->toArray(false);
        foreach ($data as $row) {

            if (!is_array($row)) {
                continue;
            }

            if (strtoupper((string)($row['quote_currency'] ?? '')) === strtoupper($quoteCurrency)) {
                $val = $row['quote'] ?? $row['rate'] ?? null;
                if (is_numeric($val)) {
                    return (float)$val;
                }
            }
        }

        throw SwopApiException::missingRate($quoteCurrency);
    }
}
