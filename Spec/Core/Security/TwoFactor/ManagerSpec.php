<?php

namespace Spec\Minds\Core\Security\TwoFactor;

use Minds\Core\Security\TOTP;
use Minds\Core\Security\TwoFactor\Delegates;
use Minds\Core\Security\TwoFactor\Manager;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ManagerSpec extends ObjectBehavior
{
    /** @var TOTP\Manager */
    protected $totpManager;

    /** @var Delegates\SMSDelegate */
    protected $smsDelegate;

    /** @var Delegates\TOTPDelegate */
    protected $totpDelegate;

    /** @var Delegates\EmailDelegate */
    protected $emailDelegate;

    public function let(
        TOTP\Manager $totpManager,
        Delegates\SMSDelegate $smsDelegate = null,
        Delegates\TOTPDelegate $totpDelegate = null,
        Delegates\EmailDelegate $emailDelegate = null
    ) {
        $this->beConstructedWith($totpManager, $smsDelegate, $totpDelegate, $emailDelegate);
        $this->totpManager = $totpManager;
        $this->smsDelegate = $smsDelegate;
        $this->totpDelegate = $totpDelegate;
        $this->emailDelegate = $emailDelegate;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_pass_gatekeeper_if_no_2fa_and_email_disabled(User $user, ServerRequest $request)
    {
        $this->totpManager->isRegistered($user)
            ->willReturn(false);

        $user->getTwoFactor()
            ->willReturn(false);

        $this->gatekeeper($user, $request, enableEmail: false);
    }

    public function it_should_block_gatekeeper_if_2fa_and_no_code(User $user, ServerRequest $request)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->totpManager->isRegistered($user)
            ->willReturn(true);

        $this->totpDelegate->onRequireTwoFactor($user)
            ->shouldBeCalled()
            ->willThrow(TwoFactorRequiredException::class);

        $this->shouldThrow(TwoFactorRequiredException::class)->duringGatekeeper($user, $request);
    }

    public function it_should_pass_gatekeeper_if_2fa_code_correct(User $user, ServerRequest $request)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->totpManager->isRegistered($user)
            ->willReturn(true);

        $request->getHeader('X-MINDS-2FA-CODE')
            ->willReturn([
                '123456'
            ]);
    
        $this->totpDelegate->onAuthenticateTwoFactor($user, '123456')
            ->shouldBeCalled();

        $this->gatekeeper($user, $request);
    }

    public function it_should_require_email_two_factor_delegate(User $user)
    {
        $this->emailDelegate->onRequireTwoFactor($user)
            ->shouldBeCalled();
        $this->requireEmailTwoFactor($user);
    }

    public function it_should_authenticate_email_two_factor_delegate(User $user)
    {
        $this->emailDelegate->onAuthenticateTwoFactor($user, '123')
            ->shouldBeCalled();
        $this->authenticateEmailTwoFactor($user, '123');
    }
}
