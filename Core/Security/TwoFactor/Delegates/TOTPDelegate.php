<?php
/**
 * TwoFactor SMS Delegate
 */
namespace Minds\Core\Security\TwoFactor\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Security\TOTP;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\TwoFactor as TwoFactorService;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Entities\User;

class TOTPDelegate implements TwoFactorDelegateInterface
{
    /** @var TwoFactorService */
    protected $twoFactorService;

    /** @var TOTP\Manager */
    protected $totpManager;

    public function __construct(TwoFactorService $twoFactorService = null, TOTP\Manager $totpManager = null)
    {
        $this->twoFactorService = $twoFactorService ?? new TwoFactorService();
        $this->totpManager = $totpManager ?? Di::_()->get('Security\TOTP\Manager');
    }

    /**
     * Should trigger the 2fa process
     * @param User $user
     * @throws TwoFactorRequiredException
     */
    public function onRequireTwoFactor(User $user): void
    {
        throw new TwoFactorRequiredException();
    }

    /**
     * Called upon authentication when the twofactor code has been provided
     * @param User $user
     * @param int $code
     * @return void
     */
    public function onAuthenticateTwoFactor(User $user, string $code): void
    {
        $opts = new TOTP\TOTPSecretQueryOpts();
        $opts->setUserGuid($user->getGuid());

        $totpSecret = $this->totpManager->get($opts);

        if (!$totpSecret) {
            throw new UnauthorizedException("TOTP device not found for user");
        }

        if (!$this->twoFactorService->verifyCode($totpSecret->getSecret(), $code)) {
            throw new TwoFactorInvalidCodeException();
        }
    }
}
