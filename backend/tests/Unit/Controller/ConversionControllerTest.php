<?php
declare(strict_types=1);

namespace Unit\Controller;

use App\Service\ConversionService;
use App\Service\ExchangeRateProviderInterface;
use App\Service\MetricsClient;
use App\Service\MoneyConversionCalculator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConversionControllerTest extends WebTestCase
{
    private static function json(array|string $content): string
    {
        return is_string($content) ? $content : json_encode($content, JSON_THROW_ON_ERROR);
    }

    private static function decode(string $content): array
    {
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testGetConvertSuccess(): void
    {
        $client = static::createClient();

        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $calc = $this->createMock(MoneyConversionCalculator::class);
        $metrics = $this->createMock(MetricsClient::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);

        $validator->method('validate')->willReturn($violations);
        $rates->method('fetchExchangeRateCurrency')->willReturnMap([
            ['USD', 1.10],
            ['EUR', 1.00],
        ]);
        $calc->method('convertAmount')->with(10.0, 1.10, 1.00)->willReturn(9.090909);
        $metrics->expects(self::once())->method('record')
            ->with('requests', self::arrayHasKey('duration_ms'), ['route' => 'convert_get']);

        static::getContainer()->set(ExchangeRateProviderInterface::class, $rates);
        static::getContainer()->set(MoneyConversionCalculator::class, $calc);
        static::getContainer()->set(MetricsClient::class, $metrics);
        static::getContainer()->set(ValidatorInterface::class, $validator);

        $client->request('GET', '/api/convert?amount=10&from=USD&to=EUR', server: [
            'HTTP_Accept-Language' => 'en-US',
        ]);

        self::assertResponseIsSuccessful();
        $data = self::decode($client->getResponse()->getContent());
        self::assertSame(10, $data['amount']);
        self::assertSame('USD', $data['from']);
        self::assertSame('EUR', $data['to']);
        self::assertSame(round(1.00 / 1.10, 6), $data['rate']);
        self::assertEqualsWithDelta(9.090909, $data['converted'], 1e-6);
        self::assertIsString($data['formatted']);
        self::assertSame('cache', $data['source']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $data['timestamp']);
    }

    public function testGetConvertValidationError(): void
    {
        $client = static::createClient();

        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $calc = $this->createMock(MoneyConversionCalculator::class);
        $metrics = $this->createMock(MetricsClient::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);

        $violations->method('count')->willReturn(1);
        $violations->method('__toString')->willReturn('bad');
        $validator->method('validate')->willReturn($violations);

        static::getContainer()->set(ExchangeRateProviderInterface::class, $rates);
        static::getContainer()->set(MoneyConversionCalculator::class, $calc);
        static::getContainer()->set(MetricsClient::class, $metrics);
        static::getContainer()->set(ValidatorInterface::class, $validator);

        $client->request('GET', '/api/convert?amount=-1&from=USD&to=EUR');

        self::assertResponseStatusCodeSame(422);
        $data = self::decode($client->getResponse()->getContent());
        self::assertSame('Validation failed: bad', $data['error']);
    }

    public function testGetConvertSwopError(): void
    {
        $client = static::createClient();

        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $calc = $this->createMock(MoneyConversionCalculator::class);
        $metrics = $this->createMock(MetricsClient::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);

        $validator->method('validate')->willReturn($violations);
        $rates->method('fetchExchangeRateCurrency')->willThrowException(new \App\Exception\SwopApiException('down'));

        static::getContainer()->set(ExchangeRateProviderInterface::class, $rates);
        static::getContainer()->set(MoneyConversionCalculator::class, $calc);
        static::getContainer()->set(MetricsClient::class, $metrics);
        static::getContainer()->set(ValidatorInterface::class, $validator);

        $client->request('GET', '/api/convert?amount=10&from=USD&to=EUR');

        self::assertResponseStatusCodeSame(502);
        $data = self::decode($client->getResponse()->getContent());
        self::assertSame('External API error', $data['error']);
    }

    public function testGetConvertUnexpectedError(): void
    {
        $client = static::createClient();

        $rates = $this->createMock(ExchangeRateProviderInterface::class);
        $calc = $this->createMock(MoneyConversionCalculator::class);
        $metrics = $this->createMock(MetricsClient::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);

        $validator->method('validate')->willReturn($violations);
        $rates->method('fetchExchangeRateCurrency')->willReturnMap([
            ['USD', 1.10],
            ['EUR', 1.00],
        ]);
        $calc->method('convertAmount')->willThrowException(new \RuntimeException('oops'));

        static::getContainer()->set(ExchangeRateProviderInterface::class, $rates);
        static::getContainer()->set(MoneyConversionCalculator::class, $calc);
        static::getContainer()->set(MetricsClient::class, $metrics);
        static::getContainer()->set(ValidatorInterface::class, $validator);

        $client->request('GET', '/api/convert?amount=10&from=USD&to=EUR');

        self::assertResponseStatusCodeSame(500);
        $data = self::decode($client->getResponse()->getContent());
        self::assertSame('Unexpected server error', $data['error']);
    }

    public function testPostConvertRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/convert', server: [
            'CONTENT_TYPE' => 'application/json',
        ], content: self::json(['amount' => 10, 'from' => 'USD', 'to' => 'EUR']));

        self::assertResponseStatusCodeSame(401);
        $data = self::decode($client->getResponse()->getContent());
        self::assertSame('Authentication required', $data['error']);
    }

    public function testPostConvertInvalidCsrf(): void
    {
        $client = static::createClient();

        $sessionFactory = static::getContainer()->get('session.factory');
        $session = $sessionFactory->createSession();
        $session->set('user', 'john');
        $session->save();
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));

        $client->request('POST', '/api/convert', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-CSRF-TOKEN' => 'bad',
        ], content: self::json(['amount' => 10, 'from' => 'USD', 'to' => 'EUR']));

        self::assertResponseStatusCodeSame(403);
        $data = self::decode($client->getResponse()->getContent());
        self::assertSame('Invalid CSRF token', $data['error']);
    }

    public function testPostConvertSuccess(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

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

        $svc = new ConversionService($rates, $calc, $metrics, $validator);
        static::getContainer()->set(ConversionService::class, $svc);

        $sessionFactory = static::getContainer()->get('session.factory');
        $session = $sessionFactory->createSession();
        $session->set('user', 'john');
        $session->save();
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));

        $client->request('GET', '/api/csrf');
        self::assertResponseIsSuccessful();
        $csrf = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];

        $client->request('POST', '/api/convert', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-CSRF-TOKEN' => $csrf,
            'HTTP_Accept-Language' => 'en-US',
        ], content: json_encode(['amount' => 5, 'from' => 'GBP', 'to' => 'EUR'], JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(5, $data['amount']);
        self::assertSame('GBP', $data['from']);
        self::assertSame('EUR', $data['to']);
        self::assertIsString($data['formatted']);
        self::assertSame('cache', $data['source']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $data['timestamp']);
    }
}
