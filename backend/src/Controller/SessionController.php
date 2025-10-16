<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/api')]
class SessionController
{
    private const CSRF_INTENT = 'convert';

    public function __construct(private CsrfTokenManagerInterface $csrf) {}

    #[Route('/csrf', name: 'csrf_get', methods: ['GET'])]
    public function getCsrfToken(SessionInterface $session): JsonResponse
    {
        $this->ensureSession($session);
        // Priming flag not strictly required, but harmless for clarity
        $session->set('_csrf_primed', true);

        $token = $this->csrf->getToken(self::CSRF_INTENT)->getValue();
        return $this->jsonResponse(['token' => $token]);
    }

    #[Route('/session', name: 'session_get', methods: ['GET'])]
    public function getSession(SessionInterface $session): JsonResponse
    {
        $this->ensureSession($session);

        $user = $session->get('user');
        return $this->jsonResponse([
            'authenticated' => (bool) $user,
            'user'          => $user ?: null,
        ]);
    }

    #[Route('/session/login', name: 'session_login', methods: ['POST'])]
    public function login(Request $req, SessionInterface $session): JsonResponse
    {
        $this->ensureSession($session);

        if ($error = $this->guardCsrf($req)) {
            return $error;
        }

        $payload = $this->parseJsonBody($req, ['username']);
        if (is_string($payload)) {
            return $this->jsonResponse(['error' => $payload], 400);
        }

        $username = trim((string) ($payload['username'] ?? ''));
        if ($username == '') {
            return $this->jsonResponse(['error' => 'Username required'], 422);
        }

        $session->set('user', $username);
        return $this->jsonResponse(['ok' => true, 'user' => $username]);
    }

    #[Route('/session/logout', name: 'session_logout', methods: ['POST'])]
    public function logout(Request $req, SessionInterface $session): JsonResponse
    {
        $this->ensureSession($session);

        if ($error = $this->guardCsrf($req)) {
            return $error;
        }

        $session->invalidate();
        return $this->jsonResponse(['ok' => true]);
    }

    /**
     * Ensure the Symfony session is started (idempotent).
     */
    private function ensureSession(SessionInterface $session): void
    {
        if (!$session->isStarted()) {
            $session->start();
        }
    }

    /**
     * Validate CSRF token from headers. Accepts both common casings.
     * Returns a JsonResponse on failure, or null on success.
     */
    private function guardCsrf(Request $req): ?JsonResponse
    {
        $tokenValue = $this->extractCsrfHeader($req);
        if (!$tokenValue || !$this->csrf->isTokenValid(new CsrfToken(self::CSRF_INTENT, $tokenValue))) {
            return $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
        return null;
    }

    /**
     * Extract CSRF token from headers, tolerant to casing.
     */
    private function extractCsrfHeader(Request $req): ?string
    {
        $hdr = $req->headers;
        return $hdr->get('X-CSRF-TOKEN')
            ?? $hdr->get('X-CSRF-Token')
            ?? null;
    }

    /**
     * Parse JSON body safely and (optionally) check required keys exist.
     * On success returns array; on failure returns string error message.
     *
     */
    private function parseJsonBody(Request $req, array $requiredKeys = []): array|string
    {
        $raw = $req->getContent() ?: '';
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            return 'Invalid JSON';
        }

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                return sprintf('Missing field: %s', $key);
            }
        }

        return $data;
    }

    private function jsonResponse(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }
}
