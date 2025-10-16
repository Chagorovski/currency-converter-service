<?php
namespace App\Service;

use App\Exception\ConversionException;
use InvalidArgumentException;

class MoneyConversionCalculator
{
    /**
     * Convert any->any using EUR as intermediary.
     * EUR->X and EUR->Y known. Then X->Y = (EUR->Y) / (EUR->X).
     */
    public function convertAmount(float $amount, float $eurToFrom, float $eurToTo): float
    {
        if ($amount < 0.0) {
            throw new InvalidArgumentException('Amount must be non-negative');
        }
        if ($eurToFrom <= 0.0 || $eurToTo <= 0.0) {
            throw ConversionException::invalidRates($eurToFrom, $eurToTo);
        }
        return round($amount * ($eurToTo / $eurToFrom), 2);
    }
}
