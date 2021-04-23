<?php
/**
 * TwoFactor Manager
 */
namespace Minds\Core\Security\TwoFactor;

use Minds\Core\Di\Di;
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

    public function __construct(
        TOTP\Manager $totpManager = null,
        Delegates\SMSDelegate $smsDelegate = null,
        Delegates\TOTPDelegate $totpDelegate = null
    ) {
        $this->totpManager = $totpManager ?? Di::_()->get('Security\TOTP\Manager');
        $this->smsDelegate = $smsDelegate ?? new Delegates\SMSDelegate();
        $this->totpDelegate = $totpDelegate ?? new Delegates\TOTPDelegate();
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
     */
    public function gatekeeper(User $user, ServerRequest $request): void
    {
        // First of all, do we evern need 2fa?
        if (!$this->isTwoFactorEnabled($user)) {
            return; // No two factor is setup, so we can allow
        }

        // Does a two factor header code exist?
        if (!$code = (string) $request->getHeader('X-MINDS-2FA-CODE')[0]) {
            // No code exists, so we trigger requireTwoFactor
            $this->requireTwoFactor($user);
        }

        // If we have got this far, then we should try to authenticate the 2fa code
        $this->authenticateTwoFactor($user, $code);
    }

    /**
     * @param User $user
     * @throws TwoFactorRequired
     */
    public function requireTwoFactor(User $user): void
    {
        if ($this->totpManager->isRegistered($user)) {
            $this->totpDelegate->onRequireTwoFactor($user);
        }

        if ($user->getTwoFactor()) {
            $this->smsDelegate->onRequireTwoFactor($user);
        }
    }

    /**
     * Called by authenticators, throws exception if fails
     * @param User $user
     * @param int $code
     * @return void
     */
    public function authenticateTwoFactor(User $user, string $code): void
    {
        if ($this->totpManager->isRegistered($user)) {
            $this->totpDelegate->onAuthenticateTwoFactor($user, $code);
            return;
        }

        if ($user->getTwoFactor()) {
            $this->smsDelegate->onAuthenticateTwoFactor($user, $code);
            return;
        }
    }
}
