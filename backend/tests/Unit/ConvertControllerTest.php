<?php

namespace Unit;

use App\Controller\ConversionController;
use App\Service\ExchangeRateProviderInterface;
use App\Service\MetricsClient;
use App\Service\MoneyConversionCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConvertControllerTest extends TestCase
{
    public function testGetConvertHappyPath(): void
    {
        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $rates->method('getEurToQuote')->willReturnMap([
            ['USD', 1.1],
            ['GBP', 0.9],
        ]);

        $validator = $this->createMock(ValidatorInterface::class);

        $controller = new ConversionController(
            $rates,
            new MoneyConversionCalculator(),
            new CsrfTokenManager(),
            new MetricsClient('', '', '', ''),
            $validator
        );

        $req = new Request(['amount' => 100, 'from' => 'USD', 'to' => 'GBP']);
        $resp = $controller->getConvert($req);

        $this->assertSame(200, $resp->getStatusCode());
        $data = json_decode($resp->getContent(), true);
        $this->assertSame(81.82, $data['converted']);
    }

    public function testGetConvertValidationError(): void
    {
        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $csrf  = $this->createMock(CsrfTokenManagerInterface::class);

        $violation = new ConstraintViolation(
            'Invalid amount',
            '',
            [],
            null,
            'amount',
            -5
        );
        $violations = new ConstraintViolationList([$violation]);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($violations);

        $controller = new ConversionController(
            $rates,
            new MoneyConversionCalculator(),
            $csrf,
            new MetricsClient('', '', '', ''),
            $validator
        );

        $req  = new Request(['amount' => -5, 'from' => 'usd', 'to' => 'x']);
        $resp = $controller->getConvert($req);

        $this->assertSame(422, $resp->getStatusCode(), $resp->getContent());
        $data = json_decode($resp->getContent(), true);
        $this->assertSame('Validation failed', $data['error']);
        $this->assertArrayHasKey('details', $data);
    }
}
