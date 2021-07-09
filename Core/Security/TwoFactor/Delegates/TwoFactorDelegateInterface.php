<?php
/**
 * TwoFactor Delegate Interface
 */
namespace Minds\Core\Security\TwoFactor\Delegates;

use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Entities\User;

interface TwoFactorDelegateInterface
{
    /**
     * Should trigger the 2fa process
     * @param User $user
     * @throws TwoFactorRequiredException
     */
    public function onRequireTwoFactor(User $user): void;

    /**
     * Called upon authentication when the twofactor code has been provided
     * @param User $user
     * @param int $code
     * @return void
     */
    public function onAuthenticateTwoFactor(User $user, string $code): void;
}
