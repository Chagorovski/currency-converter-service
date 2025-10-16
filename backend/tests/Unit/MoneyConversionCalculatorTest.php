<?php

namespace Unit;

use App\Service\MoneyConversionCalculator;
use PHPUnit\Framework\TestCase;

class MoneyConversionCalculatorTest extends TestCase
{
    public function testConversionAnyToAny()
    {
        $svc = new MoneyConversionCalculator();
        // Suppose EUR->USD=1.1 and EUR->GBP=0.9
        // USD->GBP = 0.9 / 1.1 = 0.81818..
        $result = $svc->convertAmount(100, 1.1, 0.9);
        $this->assertEquals(81.82, $result);
    }
}
