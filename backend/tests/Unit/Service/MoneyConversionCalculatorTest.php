<?php

namespace Unit\Service;

use App\Service\MoneyConversionCalculator;
use PHPUnit\Framework\TestCase;

class MoneyConversionCalculatorTest extends TestCase
{
    public function testConversionAnyToAny()
    {
        $svc = new MoneyConversionCalculator();
        $result = $svc->convertAmount(100, 1.1, 0.9);
        $this->assertEquals(81.82, $result);
    }
}
