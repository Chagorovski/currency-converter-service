<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SessionService
{
    private const CSRF_INTENT = 'convert';

    public function __construct(private CsrfTokenManagerInterface $csrf) {}

    public function startSession(SessionInterface $session): void
    {
        if (!$session->isStarted()) {
            $session->start();
        }
    }

    public function getTokenValue(): string
    {
        return $this->csrf->getToken(self::CSRF_INTENT)->getValue();
    }

    public function isCsrfValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return $this->csrf->isTokenValid(new CsrfToken(self::CSRF_INTENT, $value));
    }

    public function getUser(SessionInterface $session): ?string
    {
        $user = $session->get('user');
        return is_string($user) && $user !== '' ? $user : null;
    }

    public function setUserSession(SessionInterface $session, string $username): void
    {
        $session->set('user', $username);
    }

    public function logout(SessionInterface $session): void
    {
        $session->invalidate();
    }
}
