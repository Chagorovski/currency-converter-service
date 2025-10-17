<?php

namespace App\Service;

use App\Dto\ConversionRequest;
use App\Dto\ConversionResponse;
use App\Exception\ConversionException;
use App\Util\FormatHelper;
use NumberFormatter;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConversionService
{
    public function __construct(
        private ExchangeRateProviderInterface $rates,
        private MoneyConversionCalculator $calculator,
        private MetricsClient $metrics,
        private ValidatorInterface $validator,
    ) {}

    public function convertRates(ConversionRequest $dto, string $acceptLanguage): ConversionResponse
    {
        $startedAt = microtime(true);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ConversionException((string) $violations);
        }

        $sourceCurrency = $this->rates->fetchExchangeRateCurrency($dto->sourceCurrency);
        $targetCurrency = $this->rates->fetchExchangeRateCurrency($dto->targetCurrency);
        $converted = $this->calculator->convertAmount($dto->amount, $sourceCurrency, $targetCurrency);

        $locale = FormatHelper::normalizeLocale($acceptLanguage);
        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        if ($numberFormatter === false) {
            $numberFormatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        }
        $formatted = $numberFormatter->formatCurrency($converted, $dto->targetCurrency);

        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        $this->metrics->record('requests', ['duration_ms' => $elapsedMs], ['route' => 'convert_get']);

        return new ConversionResponse(
            amount: $dto->amount,
            from: $dto->sourceCurrency,
            to: $dto->targetCurrency,
            rate: round($targetCurrency / $sourceCurrency, 6),
            converted: $converted,
            formatted: $formatted,
            source: 'cache',
            timestamp: gmdate('c'),
        );
    }
}
