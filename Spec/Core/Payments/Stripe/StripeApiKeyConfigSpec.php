<?php

namespace Spec\Minds\Core\Payments\Stripe;

use Minds\Core\Config;
use Minds\Core\Payments\Stripe\Keys\StripeKeysService;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class StripeApiKeyConfigSpec extends ObjectBehavior
{
    private Collaborator $config;
    private Collaborator $activeSession;
    private Collaborator $keysServiceMock;

    public function let(Config $config, ActiveSession $activeSession, StripeKeysService $keysServiceMock)
    {
        $this->config = $config;
        $this->activeSession = $activeSession;
        $this->keysServiceMock = $keysServiceMock;

        $this->beConstructedWith($config, $activeSession, $keysServiceMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeApiKeyConfig::class);
    }

    public function it_should_return_the_test_creds_for_an_eligible_session_user(User $user)
    {
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn('test+phpspec@minds.local');

        $user->isEmailConfirmed()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->mockConfig();

        $this->get()->shouldBe('test_pk');
    }

    public function it_should_return_the_test_creds_for_an_eligible_arg_passed_user(User $user)
    {
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn('test+phpspec@minds.local');

        $user->isEmailConfirmed()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->activeSession->getUser()
            ->shouldNotBeCalled();

        $this->mockConfig();

        $this->get($user)->shouldBe('test_pk');
    }

    public function it_should_return_the_live_creds_for_no_user()
    {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->mockConfig();

        $this->get()->shouldBe('live_pk');
    }

    public function it_should_return_the_live_creds_for_with_non_matching_email_without_prefix_or_plus(User $user)
    {
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn('phpspec@minds.local');

        $user->isEmailConfirmed()
            ->shouldNotBeCalled()
            ->willReturn();
            
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->mockConfig();

        $this->get()->shouldBe('live_pk');
    }

    public function it_should_return_the_live_creds_for_with_non_matching_email_without_prefix(User $user)
    {
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn('+phpspec@minds.local');

        $user->isEmailConfirmed()
            ->shouldNotBeCalled()
            ->willReturn();
            
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->mockConfig();

        $this->get()->shouldBe('live_pk');
    }

    public function it_should_return_the_live_creds_for_with_non_matching_email_which_is_completely_different(User $user)
    {
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn('noreply@minds.com');

        $user->isEmailConfirmed()
            ->shouldNotBeCalled()
            ->willReturn();
            
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->mockConfig();

        $this->get()->shouldBe('live_pk');
    }

    public function it_should_return_the_live_creds_if_valid_email_is_not_confirmed(User $user)
    {
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn('test+phpspec@minds.local');

        $user->isEmailConfirmed()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->mockConfig();

        $this->get()->shouldBe('live_pk');
    }

    public function it_should_use_sec_key_from_keys_service_if_tenant(User $user)
    {
        $user->getEmail()
            ->shouldBeCalled()
            ->willReturn('phpspec@minds.local');

        $user->isEmailConfirmed()
            ->shouldNotBeCalled()
            ->willReturn();
            
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->mockConfig();

        $this->config->get('tenant_id')
            ->willReturn(1);

        $this->keysServiceMock->getSecKey()
            ->shouldBeCalled()
            ->willReturn('live_pk_from_keys_service');

        $this->get()->shouldBe('live_pk_from_keys_service');
    }


    private function mockConfig(): void
    {
        $this->config->get('payments')
            ->willReturn([
                'stripe' => [
                    'api_key' => 'live_pk',
                    'test_api_key' => 'test_pk',
                    'test_email' => 'phpspec@minds.local',
                ]
            ]);
        $this->config->get('tenant_id')
            ->willReturn(null);
    }
}
