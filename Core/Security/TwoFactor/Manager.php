<?php
/**
 * TwoFactor Manager
 */
namespace Minds\Core\Security\TwoFactor;

use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\TOTP;
use Minds\Entities\User;
use Zend\Diactoros\ServerRequest;

class Manager
{
    /** @var TOTP\Manager */
    protected $totpManager;

    /** @var Delegates\SMSDelegate */
    protected $smsDelegate;

    /** @var Delegates\TOTPDelegate */
    protected $totpDelegate;

    /** @var Delegates\EmailDelegate */
    protected $emailDelegate;

    public function __construct(
        TOTP\Manager $totpManager = null,
        Delegates\SMSDelegate $smsDelegate = null,
        Delegates\TOTPDelegate $totpDelegate = null,
        Delegates\EmailDelegate $emailDelegate = null
    ) {
        $this->totpManager = $totpManager ?? Di::_()->get('Security\TOTP\Manager');
        $this->smsDelegate = $smsDelegate ?? new Delegates\SMSDelegate();
        $this->totpDelegate = $totpDelegate ?? new Delegates\TOTPDelegate();
        $this->emailDelegate = $emailDelegate ?? new Delegates\EmailDelegate();
    }

    /**
     * Returns is two factor is required
     * @param User $user
     * @return bool
     */
    public function isTwoFactorEnabled(User $user): bool
    {
        if ($this->totpManager->isRegistered($user)) {
            return true;
        }

        if ($user->getTwoFactor()) {
            return true;
        }

        return false;
    }

    /**
     * Gatekeeper for two factor. Authenticators should call this in a delegator pattern
     * @param User $user
     * @param ServerRequest $request
     * @param bool $enableEmail - defaults to true. Disable to bypass email 2fa
     * @throws TwoFactorInvalidCodeException
     * @throws TwoFactorRequiredException
     * @throws UnauthorizedException
     */
    public function gatekeeper(User $user, ServerRequest $request, bool $enableEmail = true): void
    {
        // First of all, do we evern need 2fa?
        if (!$this->isTwoFactorEnabled($user) && !$enableEmail) {
            return; // No two factor is setup, so we can allow
        }

        // Does a two factor header code exist?
        $twoFactorHeader = $request->getHeader('X-MINDS-2FA-CODE');

        if (!$twoFactorHeader || !$code = (string) $twoFactorHeader[0]) {
            // No code exists, so we trigger requireTwoFactor
            $this->requireTwoFactor($user);
        }

        // If we have got this far, then we should try to authenticate the 2fa code
        $this->authenticateTwoFactor($user, $code ?? 0);
    }

    /**
     * @param User $user
     * @throws TwoFactorRequiredException
     */
    public function requireTwoFactor(User $user): void
    {
        /**
         * The isTrusted call below is required to allow users who are new to the platform
         * or have changed their provided email in order to be able to confirm the email address
         * via the authentication code sent to their inbox.
         */
        if ($user->isTrusted()) {
            if ($this->totpManager->isRegistered($user)) {
                $this->totpDelegate->onRequireTwoFactor($user);
            }

            if ($user->getTwoFactor()) {
                $this->smsDelegate->onRequireTwoFactor($user);
            }
        }

        $this->emailDelegate->onRequireTwoFactor($user);
    }

    /**
     * Called by authenticators, throws exception if fails
     * @param User $user
     * @param string $code
     * @return void
     * @throws TwoFactorInvalidCodeException
     * @throws UnauthorizedException
     */
    public function authenticateTwoFactor(User $user, string $code): void
    {
        /**
         * The isTrusted call below is required to allow users who are new to the platform
         * or have changed their provided email in order to be able to confirm the email address
         * via the authentication code sent to their inbox.
         */
        if ($user->isTrusted()) {
            if ($this->totpManager->isRegistered($user)) {
                $this->totpDelegate->onAuthenticateTwoFactor($user, $code);
                return;
            }

            if ($user->getTwoFactor()) {
                $this->smsDelegate->onAuthenticateTwoFactor($user, $code);
                return;
            }
        }

        $this->emailDelegate->onAuthenticateTwoFactor($user, $code);
    }
}
