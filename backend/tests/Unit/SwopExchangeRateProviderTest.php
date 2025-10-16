<?php

namespace Unit;

use App\Exception\SwopApiException;
use App\Service\SwopExchangeRateProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SwopExchangeRateProviderTest extends TestCase
{
    public function testParsesRows(): void
    {
        $payload = json_encode([
            ['base_currency' => 'EUR', 'quote_currency' => 'USD', 'quote' => 1.1],
            ['base_currency' => 'EUR', 'quote_currency' => 'GBP', 'quote' => 0.9],
        ]);

        // Callable returns a NEW MockResponse each time (so we can make multiple requests)
        $client = new MockHttpClient(fn() => new MockResponse($payload, ['http_code' => 200]));

        $svc = new SwopExchangeRateProvider(
            $client,
            'https://swop.cx/rest',
            'key',
            new ArrayAdapter(),
            60
        );

        self::assertSame(1.1, $svc->getEurToQuote('USD'));
        self::assertSame(0.9, $svc->getEurToQuote('GBP'));
    }

    public function testMissingRateThrows(): void
    {
        $payload = json_encode([
            ['base_currency' => 'EUR', 'quote_currency' => 'USD', 'quote' => 1.1],
        ]);

        $client = new MockHttpClient(fn() => new MockResponse($payload, ['http_code' => 200]));

        $svc = new SwopExchangeRateProvider(
            $client,
            'https://swop.cx/rest',
            'key',
            new ArrayAdapter(),
            60
        );

        $this->expectException(SwopApiException::class);
        $svc->getEurToQuote('GBP');
    }
}