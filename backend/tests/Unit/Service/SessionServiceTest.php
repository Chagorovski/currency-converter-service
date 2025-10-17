<?php

namespace Unit\Service;

use App\Service\SessionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SessionServiceTest extends TestCase
{

    private function sessionService(?CsrfTokenManagerInterface $csrf = null): SessionService
    {
        return new SessionService($csrf ?? $this->createMock(CsrfTokenManagerInterface::class));
    }

    public function testEnsureStartsSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())->method('isStarted')->willReturn(false);
        $session->expects(self::once())->method('start');
        $this->sessionService()->startSession($session);
    }

    public function testEnsureDoesNotStartIfAlreadyStarted(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())->method('isStarted')->willReturn(true);
        $session->expects(self::never())->method('start');
        $this->sessionService()->startSession($session);
    }

    public function testTokenReturnsValue(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('getToken')->willReturn(new CsrfToken('convert', 'abc123'));
        self::assertSame('abc123', $this->sessionService($csrf)->getTokenValue());
    }

    public function testIsCsrfValidRejectsNullAndEmpty(): void
    {
        self::assertFalse($this->sessionService()->isCsrfValid(null));
        self::assertFalse($this->sessionService()->isCsrfValid(''));
    }

    public function testIsCsrfValidDelegatesToManager(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects(self::once())->method('isTokenValid')->with(self::isInstanceOf(CsrfToken::class))->willReturn(true);
        $svc = new SessionService($csrf);
        self::assertTrue($svc->isCsrfValid('ok'));
    }

    public function testUserReturnsNullWhenMissingOrEmptyOrNonString(): void
    {
        $session = $this->createConfiguredMock(SessionInterface::class, ['get' => null]);
        self::assertNull($this->sessionService()->getUser($session));

        $session = $this->createConfiguredMock(SessionInterface::class, ['get' => '']);
        self::assertNull($this->sessionService()->getUser($session));

        $session = $this->createConfiguredMock(SessionInterface::class, ['get' => 123]);
        self::assertNull($this->sessionService()->getUser($session));
    }

    public function testUserReturnsString(): void
    {
        $session = $this->createConfiguredMock(SessionInterface::class, ['get' => 'john']);
        self::assertSame('john', $this->sessionService()->getUser($session));
    }

    public function testLoginSetsUsername(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())->method('set')->with('user', 'john');
        $this->sessionService()->setUserSession($session, 'john');
    }

    public function testLogoutInvalidates(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())->method('invalidate');
        $this->sessionService()->logout($session);
    }
}
