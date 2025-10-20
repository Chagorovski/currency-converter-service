<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dto\ConversionRequest;
use App\Dto\ErrorResponse;
use App\Exception\ConversionException;
use App\Exception\SwopApiException;
use App\Service\ConversionService;
use App\Service\SessionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

#[Route('/api')]
final class ConversionController extends AbstractController
{
    public function __construct(
        private ConversionService $service,
        private SessionService $sessions,
        private CsrfTokenManagerInterface $csrf,
    ) {}

    #[Route('/convert', name: 'convert_get', methods: ['GET'])]
    public function getConvert(Request $req, SessionInterface $session): JsonResponse
    {
        if (!$this->sessions->getUser($session)) {
            return $this->json(new ErrorResponse('Not authenticated'), 401);
        }

        try {
            $dto = ConversionRequest::fromArray($req->query->all());
            $result = $this->service->convertRates($dto, $req->headers->get('Accept-Language') ?? 'en-US');
            return $this->json($result);
        } catch (SwopApiException $e) {
            return $this->json(new ErrorResponse('External API error'), 502);
        } catch (ConversionException $e) {
            return $this->json(new ErrorResponse('Validation failed: ' . $e->getMessage()), 422);
        } catch (Throwable $e) {
            return $this->json(new ErrorResponse('Unexpected server error'), 500);
        }
    }

    #[Route('/convert', name: 'convert_post', methods: ['POST'])]
    public function postConvert(Request $req, SessionInterface $session): JsonResponse
    {
        if (!$this->sessions->getUser($session)) {
            return $this->json(new ErrorResponse('Not authenticated'), 401);
        }

        $user = $session->get('user');
        if (!is_string($user) || $user === '') {
            return $this->json(new ErrorResponse('Authentication required'), 401);
        }

        $token = $this->csrfFromHeaders($req);
        if (!$token || !$this->csrf->isTokenValid(new CsrfToken('convert', $token))) {
            return $this->json(new ErrorResponse('Invalid CSRF token'), 403);
        }

        $json = json_decode($req->getContent() ?: '{}', true) ?: [];
        $proxy = new Request(query: $json);
        return $this->getConvert($proxy, $session);
    }

    private function csrfFromHeaders(Request $request): ?string
    {
        return $request->headers->get('X-CSRF-TOKEN')
            ?? $request->headers->get('X-CSRF-Token');
    }
}
