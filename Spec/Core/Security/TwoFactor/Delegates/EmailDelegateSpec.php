<?php

namespace Spec\Minds\Core\Security\TwoFactor\Delegates;

use PhpSpec\ObjectBehavior;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Email\V2\Campaigns\Recurring\TwoFactor\TwoFactor as TwoFactorEmail;
use Minds\Core\Security\TwoFactor as TwoFactorService;
use Minds\Core\Email\Confirmation\Manager as EmailConfirmationManager;
use Minds\Core\Log\Logger;
use Minds\Core\Security\TwoFactor\Delegates\EmailDelegate;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecret;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecretStore;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Entities\User;
use Prophecy\Argument;

class EmailDelegateSpec extends ObjectBehavior
{
    /** @var TwoFactorService */
    protected $twoFactorService;

    /** @var Logger */
    protected $logger;

    /** @var TwoFactorEmail */
    protected $twoFactorEmail;

    /** @var TwoFactorSecretStore */
    protected $twoFactorSecretStore;

    /** @var EmailConfirmationManager */
    protected $emailConfirmation;

    public function let(
        TwoFactorService $twoFactorService,
        Logger $logger,
        TwoFactorEmail $twoFactorEmail,
        TwoFactorSecretStore $twoFactorSecretStore,
        EmailConfirmationManager $emailConfirmation
    ) {
        $this->beConstructedWith(
            $twoFactorService,
            $logger,
            $twoFactorEmail,
            $twoFactorSecretStore,
            $emailConfirmation
        );

        $this->twoFactorService = $twoFactorService;
        $this->logger = $logger;
        $this->twoFactorEmail = $twoFactorEmail;
        $this->twoFactorSecretStore = $twoFactorSecretStore;
        $this->emailConfirmation = $emailConfirmation;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmailDelegate::class);
    }

    public function it_should_get_new_code_if_NO_secret_exists_and_email_it(
        User $user
    ) {
        $this->twoFactorSecretStore->get($user)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->twoFactorService->createSecret()
            ->shouldBeCalled()
            ->willReturn('~secret~');

        $this->twoFactorService->getCode(
            secret: '~secret~',
            timeSlice: 1
        )
            ->shouldBeCalled()
            ->willReturn('~code~');

        $this->twoFactorEmail->setUser($user)
            ->shouldBeCalled();

        $this->twoFactorEmail->setCode('~code~')
            ->shouldBeCalled();

        $this->twoFactorEmail->send()
            ->shouldBeCalled();

        $this->twoFactorSecretStore->set($user, '~secret~')
            ->shouldBeCalled()
            ->willReturn('~key@');

        $this->shouldThrow(TwoFactorRequiredException::class)
            ->during('onRequireTwoFactor', [ $user ]);
    }

    public function it_should_use_existing_code_if_one_exists(
        User $user,
        TwoFactorSecret $twoFactorSecret
    ) {
        $_SERVER['HTTP_X_MINDS_EMAIL_2FA_KEY'] = '~2faKey~';

        $twoFactorSecret->getSecret()
            ->shouldBeCalled()
            ->willReturn('~secret~');

        $this->twoFactorSecretStore->getByKey('~2faKey~')
            ->shouldBeCalled()
            ->willReturn($twoFactorSecret);

        $this->twoFactorService->createSecret()
            ->shouldNotBeCalled()
            ->willReturn('~secret~');

        $this->twoFactorService->getCode(
            secret: '~secret~',
            timeSlice: 1
        )
            ->shouldBeCalled()
            ->willReturn('~code~');

        $this->twoFactorEmail->setUser($user)
            ->shouldBeCalled();

        $this->twoFactorEmail->setCode('~code~')
            ->shouldBeCalled();

        $this->twoFactorEmail->send()
            ->shouldBeCalled();

        $this->shouldThrow(TwoFactorRequiredException::class)
            ->during('onRequireTwoFactor', [ $user ]);

        unset($_SERVER['HTTP_X_MINDS_EMAIL_2FA_KEY']);
    }

    public function it_should_NOT_authenticate_a_two_factor_code_with_no_matching_stored_object(
        User $user
    ) {
        $this->twoFactorSecretStore->getByKey('')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(TwoFactorInvalidCodeException::class)
            ->during('onAuthenticateTwoFactor', [ $user, '~code~']);
    }

    public function it_should_NOT_authenticate_a_two_factor_code_that_has_expired(
        User $user,
        TwoFactorSecret $twoFactorSecret
    ) {
        $twoFactorSecret->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid~');

        $twoFactorSecret->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->twoFactorSecretStore->getByKey('')
            ->shouldBeCalled()
            ->willReturn($twoFactorSecret);

        $this->twoFactorSecretStore->getTtl($user)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->twoFactorService->verifyCode(
            secret: '~secret~',
            code: '~code~',
            discrepancy: 1,
            currentTimeSlice: 1
        )
            ->shouldNotBeCalled();

        $this->shouldThrow(TwoFactorInvalidCodeException::class)
            ->during('onAuthenticateTwoFactor', [ $user, '~code~']);
    }


    public function it_should_NOT_authenticate_a_two_factor_code_with_a_guid_mismatch(
        User $user,
        TwoFactorSecret $twoFactorSecret
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid1~');

        $twoFactorSecret->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid2~');

        $twoFactorSecret->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(999999999999999999);

        $this->twoFactorSecretStore->getByKey('')
            ->shouldBeCalled()
            ->willReturn($twoFactorSecret);

        $this->twoFactorSecretStore->getTtl($user)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->twoFactorService->verifyCode(
            secret: '~secret~',
            code: '~code~',
            discrepancy: 1,
            currentTimeSlice: 1
        )
            ->shouldNotBeCalled();

        $this->shouldThrow(TwoFactorInvalidCodeException::class)
            ->during('onAuthenticateTwoFactor', [ $user, '~code~']);
    }

    public function it_should_NOT_authenticate_an_INVALID_two_factor_code(
        User $user,
        TwoFactorSecret $twoFactorSecret
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid~');

        $twoFactorSecret->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid~');

        $twoFactorSecret->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(999999999999999999);

        $twoFactorSecret->getSecret()
            ->shouldBeCalled()
            ->willReturn('~secret~');

        $this->twoFactorSecretStore->getByKey('')
            ->shouldBeCalled()
            ->willReturn($twoFactorSecret);

        $this->twoFactorSecretStore->getTtl($user)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->twoFactorService->verifyCode(
            secret: '~secret~',
            code: '~code~',
            discrepancy: 1,
            currentTimeSlice: 1
        )
            ->shouldBeCalled()
            ->willReturn(false);

        $this->shouldThrow(TwoFactorInvalidCodeException::class)
            ->during('onAuthenticateTwoFactor', [ $user, '~code~']);
    }

    public function it_should_authenticate_an_VALID_two_factor_code(
        User $user,
        TwoFactorSecret $twoFactorSecret
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid~');

        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $twoFactorSecret->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid~');

        $twoFactorSecret->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(999999999999999999);

        $twoFactorSecret->getSecret()
            ->shouldBeCalled()
            ->willReturn('~secret~');

        $this->twoFactorSecretStore->getByKey('')
            ->shouldBeCalled()
            ->willReturn($twoFactorSecret);

        $this->twoFactorSecretStore->getTtl($user)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->twoFactorService->verifyCode(
            secret: '~secret~',
            code: '~code~',
            discrepancy: 1,
            currentTimeSlice: 1
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->twoFactorSecretStore->delete('')
            ->shouldBeCalled();

        $this->shouldNotThrow(TwoFactorInvalidCodeException::class)
            ->during('onAuthenticateTwoFactor', [ $user, '~code~']);
    }

    public function it_should_authenticate_an_VALID_two_factor_code_AND_trust_untrusted_user(
        User $user,
        TwoFactorSecret $twoFactorSecret
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid~');

        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(false);

        $twoFactorSecret->getGuid()
            ->shouldBeCalled()
            ->willReturn('~guid~');

        $twoFactorSecret->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(999999999999999999);

        $twoFactorSecret->getSecret()
            ->shouldBeCalled()
            ->willReturn('~secret~');

        $this->twoFactorSecretStore->getByKey('')
            ->shouldBeCalled()
            ->willReturn($twoFactorSecret);

        $this->twoFactorSecretStore->getTtl($user)
            ->shouldBeCalled()
            ->willReturn(0);

        $this->twoFactorService->verifyCode(
            secret: '~secret~',
            code: '~code~',
            discrepancy: 1,
            currentTimeSlice: 1
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->emailConfirmation->approveConfirmation($user)
            ->shouldBeCalled();
        
        $this->twoFactorSecretStore->delete('')
            ->shouldBeCalled();

        $this->shouldNotThrow(TwoFactorInvalidCodeException::class)
            ->during('onAuthenticateTwoFactor', [ $user, '~code~']);
    }
}
