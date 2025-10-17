<?php

namespace Unit\Service;

use App\Dto\ConversionRequest;
use App\Exception\ConversionException;
use App\Service\ConversionService;
use App\Service\ExchangeRateProviderInterface;
use App\Service\MetricsClient;
use App\Service\MoneyConversionCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConversionServiceTest extends TestCase
{
    public function testConvertSuccess(): void
    {
        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $calc = $this->createMock(MoneyConversionCalculator::class);
        $metrics = $this->createMock(MetricsClient::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);

        $violations->method('count')->willReturn(0);
        $validator->method('validate')->willReturn($violations);

        $rates->method('fetchExchangeRateCurrency')->willReturnMap([
            ['USD', 1.10],
            ['EUR', 1.00],
        ]);
        $calc->method('convertAmount')->with(100.0, 1.10, 1.00)->willReturn(90.9090909091);

        $metrics->expects(self::once())->method('record')
            ->with('requests', self::arrayHasKey('duration_ms'), ['route' => 'convert_get']);

        $conversionService = new ConversionService($rates, $calc, $metrics, $validator);

        $dto = ConversionRequest::fromArray([
            'amount' => 100.0,
            'from'   => 'USD',
            'to'     => 'EUR',
        ]);

        $res = $conversionService->convertRates($dto, 'en-US');

        self::assertSame(100.0, $res->amount);
        self::assertSame('USD', $res->from);
        self::assertSame('EUR', $res->to);
        self::assertSame(round(1.00 / 1.10, 6), $res->rate);
        self::assertEqualsWithDelta(90.9090909091, $res->converted, 1e-9);
        self::assertIsString($res->formatted);
        self::assertSame('cache', $res->source);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $res->timestamp);
    }

    public function testConvertUsesLocaleFallbackOnBadAcceptLanguage(): void
    {
        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $calc = $this->createMock(MoneyConversionCalculator::class);
        $metrics = $this->createMock(MetricsClient::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);

        $violations->method('count')->willReturn(0);
        $validator->method('validate')->willReturn($violations);

        $rates->method('fetchExchangeRateCurrency')->willReturnMap([
            ['GBP', 0.85],
            ['EUR', 1.00],
        ]);
        $calc->method('convertAmount')->with(5.0, 0.85, 1.00)->willReturn(5.8823529412);

        $conversionService = new ConversionService($rates, $calc, $metrics, $validator);

        $dto = ConversionRequest::fromArray([
            'amount' => 5.0,
            'from'   => 'GBP',
            'to'     => 'EUR',
        ]);

        $res = $conversionService->convertRates($dto, '???');

        self::assertEqualsWithDelta(5.8823529412, $res->converted, 1e-9);
        self::assertIsString($res->formatted);
        self::assertNotSame('', $res->formatted);
    }

    public function testConvertValidationFailure(): void
    {
        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $calc = $this->createMock(MoneyConversionCalculator::class);
        $metrics = $this->createMock(MetricsClient::class);
        $validator = $this->createMock(ValidatorInterface::class);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('count')->willReturn(1);
        $violations->method('__toString')->willReturn('bad');
        $validator->method('validate')->willReturn($violations);

        $conversionService = new ConversionService($rates, $calc, $metrics, $validator);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('bad');

        $dto = ConversionRequest::fromArray([
            'amount' => -1.0,
            'from'   => 'USD',
            'to'     => 'EUR',
        ]);

        $conversionService->convertRates($dto, 'en-US');
    }
}