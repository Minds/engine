<?php

namespace Spec\Minds\Core\Security\TwoFactor\Delegates;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Security\TwoFactor;
use Minds\Core\Security\TwoFactor\Delegates\SMSDelegate;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Entities\User;
use Minds\Core\SMS;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SMSDelegateSpec extends ObjectBehavior
{
    /** @var SMS\Services\Twilio */
    protected $smsService;

    /** @var PsrWrapper */
    protected $cache;

    public function let(SMS\Services\Twilio $smsService, PsrWrapper $cache)
    {
        $this->beConstructedWith(null, $smsService, $cache);
        $this->smsService = $smsService;
        $this->cache = $cache;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SMSDelegate::class);
    }

    public function it_should_send_sms_on_2fa(User $user)
    {
        $user->getGuid()
            ->willReturn('123');
        $user->get('guid')
            ->willReturn('123');
        $user->getUsername()
            ->willReturn('mark');
        $user->get('username')
            ->willReturn('mark');
        $user->get('telno')
            ->willReturn('+44000000000000');
        $user->get('salt')
            ->willReturn('salt');

        $this->smsService->send(Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->shouldThrow(TwoFactorRequiredException::class)->duringOnRequireTwoFactor($user);
    }

    public function it_should_throw_invalid_code(User $user)
    {
        $_SERVER['HTTP_X_MINDS_SMS_2FA_KEY'] = 'fake-key';
        $code = '123456';

        $user->getGuid()
            ->willReturn('123');

        $this->cache->get('fake-key')
            ->willReturn(json_encode([
                '_guid' => '123',
                'ts' => time() - 30,
                'secret' => 'not-so-secret'
            ]));

        $this->shouldThrow(TwoFactorInvalidCodeException::class)->duringOnAuthenticateTwoFactor($user, $code);
    }

    public function it_should_not_throw_exceptions(User $user)
    {
        $_SERVER['HTTP_X_MINDS_SMS_2FA_KEY'] = 'fake-key';

        $user->getGuid()
            ->willReturn('123');

        $this->cache->get('fake-key')
            ->willReturn(json_encode([
                '_guid' => '123',
                'ts' => time() - 30,
                'secret' => 'not-so-secret'
            ]));

        $twoFactorService = new TwoFactor();
        $code = $twoFactorService->getCode('not-so-secrett');

        $this->onAuthenticateTwoFactor($user, $code);
    }
}
