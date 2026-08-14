<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Model\Entity\User;
use App\Service\Authorization\PermissionService;
use App\Service\Security\SensitiveSession;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Drops sessions whose auth_version no longer matches, and gates staff MFA.
 */
final class SessionIntegrityMiddleware implements MiddlewareInterface
{
    use LocatorAwareTrait;

    public const SESSION_VERSION = 'AuthVersion';

    public const SESSION_MFA = 'MfaVerified';

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$request instanceof ServerRequest) {
            return $handler->handle($request);
        }

        $identity = $request->getAttribute('identity');
        if ($identity === null) {
            return $handler->handle($request);
        }

        $userId = (int)$identity->getIdentifier();
        $users = $this->fetchTable('Users');
        try {
            $user = $users->get($userId);
        } catch (Throwable) {
            return $this->forget($request, $handler);
        }

        $status = (string)($user->get('status') ?: 'active');
        if ($status !== 'active' || $user->get('deleted') !== null) {
            return $this->forget($request, $handler);
        }

        $session = $request->getSession();
        $currentVersion = (int)($user->get('auth_version') ?: 1);
        $sessionVersion = $session->read(self::SESSION_VERSION);
        if ($sessionVersion === null || $sessionVersion === '') {
            $session->write(self::SESSION_VERSION, $currentVersion);
        } elseif ((int)$sessionVersion !== $currentVersion) {
            return $this->forget($request, $handler);
        }

        if ($this->requiresStaffMfa($user) && !$this->mfaPathAllowed($request, $user)) {
            $enabled = (bool)$user->get('mfa_enabled');
            $verified = (bool)$session->read(self::SESSION_MFA);
            if ($enabled && !$verified) {
                return (new Response())->withStatus(302)->withHeader('Location', '/login/mfa');
            }
            if (!$enabled) {
                return (new Response())->withStatus(302)->withHeader('Location', '/login/mfa-setup');
            }
        }

        return $handler->handle($request);
    }

    /**
     * @param \App\Model\Entity\User $user Identity.
     * @return bool
     */
    private function requiresStaffMfa(User $user): bool
    {
        $raw = env('SECURITY_REQUIRE_STAFF_MFA');
        if ($raw === null || $raw === false || $raw === '') {
            $required = !Configure::read('debug');
        } else {
            $required = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        }
        if (!$required) {
            return false;
        }
        $role = (string)($user->get('role') ?: '');
        if ($role !== '' && $role !== 'customer') {
            return true;
        }

        return (new PermissionService())->hasAny((int)$user->id);
    }

    /**
     * @param \Cake\Http\ServerRequest $request Request.
     * @param \App\Model\Entity\User $user Identity.
     * @return bool
     */
    private function mfaPathAllowed(ServerRequest $request, User $user): bool
    {
        $path = rtrim($request->getUri()->getPath(), '/') ?: '/';
        if (in_array($path, ['/logout', '/login'], true)) {
            return true;
        }
        if ($path === '/login/mfa') {
            return true;
        }

        return $path === '/login/mfa-setup' && !(bool)$user->get('mfa_enabled');
    }

    /**
     * @param \Cake\Http\ServerRequest $request Request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler Next handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function forget(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getSession();
        SensitiveSession::clear($session);
        $request = $request->withoutAttribute('identity');

        return $handler->handle($request);
    }
}
