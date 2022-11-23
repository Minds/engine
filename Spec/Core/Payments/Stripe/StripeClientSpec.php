<?php

namespace Spec\Minds\Core\Payments\Stripe;

use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class StripeClientSpec extends ObjectBehavior
{
    /** @var Config */
    private $configMock;

    /** @var ActiveSession */
    private $activeSessionMock;

    public function let(Config $configMock, ActiveSession $activeSessionMock)
    {
        //$this->beConstructedWith(null, $configMock, $activeSessionMock);
        $this->configMock = $configMock;
        $this->activeSessionMock = $activeSessionMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeClient::class);
    }

    public function it_should_use_the_live_creds()
    {
        $this->mockConfig();
        $this->beConstructedWith(null, $this->configMock, $this->activeSessionMock);

        $this->getApiKey()->shouldBe('live_pk');
    }

    public function it_should_use_the_test_creds(User $userMock)
    {
        $this->mockConfig();

        $this->activeSessionMock->getUser()->willReturn($userMock);

        $userMock->getEmail()->willReturn('phpspec@minds.local');
        $userMock->isEmailConfirmed()->willReturn(true);

        $this->beConstructedWith(null, $this->configMock, $this->activeSessionMock);

        $this->getApiKey()->shouldBe('test_pk');
    }

    public function it_should_use_the_live_creds_because_of_foregd_email(User $userMock)
    {
        $this->mockConfig();

        $this->activeSessionMock->getUser()->willReturn($userMock);

        $userMock->getEmail()->willReturn('phpspec@minds.local');
        $userMock->isEmailConfirmed()->willReturn(false);

        $this->beConstructedWith(null, $this->configMock, $this->activeSessionMock);

        $this->getApiKey()->shouldBe('live_pk');
    }

    private function mockConfig(): void
    {
        $this->configMock->get('payments')
            ->willReturn([
                'stripe' => [
                    'api_key' => 'live_pk',
                    'test_api_key' => 'test_pk',
                    'test_email' => 'phpspec@minds.local',
                ]
            ]);
    }
}
