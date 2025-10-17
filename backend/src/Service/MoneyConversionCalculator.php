<?php
namespace App\Service;

use App\Exception\ConversionException;
use InvalidArgumentException;

class MoneyConversionCalculator
{

    public function convertAmount(float $amount, float $eurToSourceRate, float $eurToTargetRate): float
    {
        if ($amount < 0.0) {
            throw new InvalidArgumentException('Amount must be non-negative');
        }
        if ($eurToSourceRate <= 0.0 || $eurToTargetRate <= 0.0) {
            throw ConversionException::invalidRates($eurToSourceRate, $eurToTargetRate);
        }
        return round($amount * ($eurToTargetRate / $eurToSourceRate), 2);
    }
}
