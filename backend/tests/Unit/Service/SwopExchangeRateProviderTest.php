<?php

namespace Unit\Service;

use App\Exception\SwopApiException;
use App\Service\ExchangeRateClientInterface;
use App\Service\SwopExchangeRateProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class SwopExchangeRateProviderTest extends TestCase
{
    public function testFetchesAndCachesRates(): void
    {
        $client = $this->createMock(ExchangeRateClientInterface::class);

        $client->expects(self::exactly(2))
            ->method('fetchExchangeCurrency')
            ->willReturnCallback(function (string $code) {
                static $call = 0;
                $call++;

                if ($call === 1) {
                    TestCase::assertSame('USD', $code);
                    return 1.1;
                }

                if ($call === 2) {
                    TestCase::assertSame('GBP', $code);
                    return 0.9;
                }

                TestCase::fail('fetchExchangeCurrency called more than twice');
            });

        $svc = new SwopExchangeRateProvider($client, $this->arrayCache(), 60);

        self::assertSame(1.1, $svc->fetchExchangeRateCurrency('USD'));
        self::assertSame(0.9, $svc->fetchExchangeRateCurrency('GBP'));
        self::assertSame(1.1, $svc->fetchExchangeRateCurrency('usd'));
    }

    public function testMissingRateThrows(): void
    {
        $client = $this->createMock(ExchangeRateClientInterface::class);
        $client->expects(self::once())
            ->method('fetchExchangeCurrency')
            ->with('GBP')
            ->willReturn(0.0);

        $svc = new SwopExchangeRateProvider($client, $this->arrayCache(), 60);

        $this->expectException(SwopApiException::class);
        $svc->fetchExchangeRateCurrency('GBP');
    }

    private function arrayCache(): ArrayAdapter
    {
        return new ArrayAdapter();
    }
}