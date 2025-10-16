<?php
declare(strict_types=1);

namespace App\Controller;

use App\DTO\ConversionRequest;
use App\Exception\ConversionException;
use App\Exception\SwopApiException;
use App\Service\ExchangeRateProviderInterface;
use App\Service\MoneyConversionCalculator;
use App\Service\MetricsClient;
use NumberFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class ConversionController
{
    public function __construct(
        private ExchangeRateProviderInterface $rates,
        private MoneyConversionCalculator $calculator,
        private CsrfTokenManagerInterface $csrf,
        private MetricsClient $metrics,
        private ValidatorInterface $validator,
    ) {}

    /** GET /api/convert?amount=..&from=USD&to=GBP */
    #[Route('/api/convert', name: 'convert_get', methods: ['GET'])]
    public function getConvert(Request $req): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $dto = ConversionRequest::fromArray($req->query->all());
            $violations = $this->validator->validate($dto);

            if (count($violations) > 0) {
                return new JsonResponse([
                    'error'   => 'Validation failed',
                    'details' => (string) $violations,
                ], 422);
            }

            $eurToFrom = $this->rates->getEurToQuote($dto->sourceCurrency);
            $eurToTo   = $this->rates->getEurToQuote($dto->targetCurrency);
            $converted = $this->calculator->convertAmount($dto->amount, $eurToFrom, $eurToTo);

            $localeHeader = $req->headers->get('Accept-Language') ?? 'en-US';
            $locale = $this->normalizeLocale($localeHeader);
            $fmt    = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            if ($fmt === false) {
                // Fallback to a safe default if ICU lacks the requested locale
                $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
            }
            $formatted = $fmt->formatCurrency($converted, $dto->targetCurrency);

            $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
            $this->metrics->record('requests', ['duration_ms' => $elapsedMs], ['route' => 'convert_get']);

            return new JsonResponse([
                'amount'    => $dto->amount,
                'from'      => $dto->sourceCurrency,
                'to'        => $dto->targetCurrency,
                'rate'      => round($eurToTo / $eurToFrom, 6),
                'converted' => $converted,
                'formatted' => $formatted,
                'source'    => 'cache', // SwopExchangeRateProvider handles cache internally
                'timestamp' => gmdate('c'),
            ]);

        } catch (SwopApiException $e) {
            $this->metrics->record('errors', ['count' => 1], ['route' => 'convert_get', 'error' => 'swop']);
            return new JsonResponse(['error' => 'External API error', 'details' => $e->getMessage()], 502);
        } catch (ConversionException $e) {
            return new JsonResponse(['error' => 'Conversion failed', 'details' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            $this->metrics->record('errors', ['count' => 1], ['route' => 'convert_get', 'error' => 'unexpected']);
            return new JsonResponse(['error' => 'Unexpected server error', 'details' => $e->getMessage()], 500);
        }
    }

    /** POST /api/convert {amount, from, to} + X-CSRF-Token or X-CSRF-TOKEN header */
    #[Route('/api/convert', name: 'convert_post', methods: ['POST'])]
    public function postConvert(Request $req, SessionInterface $session): JsonResponse
    {
        if (!$session->get('user')) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $headerToken = $req->headers->get('X-CSRF-TOKEN') ?? $req->headers->get('X-CSRF-Token');
        if (!$headerToken || !$this->csrf->isTokenValid(new CsrfToken('convert', $headerToken))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        $json = json_decode($req->getContent() ?: '{}', true) ?: [];
        $proxy = new Request(query: $json);

        // Reuse validation + conversion by delegating to GET handler
        return $this->getConvert($proxy);
    }

    /**
     * Normalize Accept-Language value into an ICU locale expected by NumberFormatter.
     * Example: "en-US,en;q=0.9" -> "en_US"
     */
    private function normalizeLocale(string $acceptLanguage): string
    {
        // take the first language token before ',' and strip parameters after ';'
        $first = explode(',', $acceptLanguage, 2)[0] ?? 'en-US';
        $first = explode(';', $first, 2)[0] ?? 'en-US';
        $first = trim($first) ?: 'en-US';

        // ICU expects underscore
        $icu = str_replace('-', '_', $first);

        // Basic guard: ensure at least language; default to en_US
        if ($icu === '' || preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $icu) !== 1) {
            return 'en_US';
        }
        return $icu;
    }
}
