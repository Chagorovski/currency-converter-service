<?php
declare(strict_types=1);

namespace App\Controller;

use App\Dto\CsrfTokenResponse;
use App\Dto\ErrorResponse;
use App\Dto\LoginResponse;
use App\Dto\SessionResponse;
use App\Service\SessionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/api')]
final class SessionController extends AbstractController
{
    public function __construct(private SessionService $sessions) {}

    #[Route('/csrf', name: 'csrf_get', methods: ['GET'])]
    public function getCsrfToken(SessionInterface $session): JsonResponse
    {
        $this->sessions->startSession($session);
        return $this->json(new CsrfTokenResponse($this->sessions->getTokenValue()));
    }

    #[Route('/session', name: 'session_get', methods: ['GET'])]
    public function getSession(SessionInterface $session): JsonResponse
    {
        $this->sessions->startSession($session);
        $user = $this->sessions->getUser($session);
        return $this->json(new SessionResponse($user !== null, $user));
    }

    #[Route('/session/login', name: 'session_login', methods: ['POST'])]
    public function login(Request $request, SessionInterface $session): JsonResponse
    {
        $this->sessions->startSession($session);
        $token = $this->getCsrfFromHeader($request);
        if (!$this->sessions->isCsrfValid($token)) {
            return $this->json(new ErrorResponse('Invalid CSRF token'), 403);
        }

        $data = [];
        try {
            $data = $request->getContent() !== '' ?
                json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) :
                [];
        } catch (Throwable) {
            // ignore, stay with empty array
        }

        $username = trim((string)($data['username'] ?? ''));

        if ($username === '') {
            return $this->json(new ErrorResponse('Username required'), 422);
        }

        $this->sessions->setUserSession($session, $username);
        return $this->json(new LoginResponse(true, $username));
    }

    #[Route('/session/logout', name: 'session_logout', methods: ['POST'])]
    public function logout(Request $request, SessionInterface $session): JsonResponse
    {
        $this->sessions->startSession($session);
        $token = $this->getCsrfFromHeader($request);

        if (!$this->sessions->isCsrfValid($token)) {
            return $this->json(new ErrorResponse('Invalid CSRF token'), 403);
        }

        $this->sessions->logout($session);
        return $this->json(new LoginResponse(true));
    }

    private function getCsrfFromHeader(Request $request): ?string
    {
        $currentHeaders = $request->headers;
        return $currentHeaders->get('X-CSRF-TOKEN') ?? $currentHeaders->get('X-CSRF-Token') ?? null;
    }
}
