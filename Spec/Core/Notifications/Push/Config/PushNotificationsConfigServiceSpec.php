<?php

namespace Spec\Minds\Core\Notifications\Push\Config;

use Minds\Core\Notifications\Push\Config\PushNotificationConfig;
use Minds\Core\Notifications\Push\Config\PushNotificationsConfigRepository;
use Minds\Core\Notifications\Push\Config\PushNotificationsConfigService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PushNotificationsConfigServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;

    public function let(PushNotificationsConfigRepository $repositoryMock)
    {
        $this->beConstructedWith($repositoryMock);
        $this->repositoryMock = $repositoryMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PushNotificationsConfigService::class);
    }

    public function it_should_return_configs()
    {
        $this->repositoryMock->get(1)
            ->shouldBeCalled()
            ->willReturn(
                new PushNotificationConfig(
                    apnsTeamId: 'AAAAAAAAAA',
                    apnsKey: "-----BEGIN PRIVATE KEY-----
                    AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
                    AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
                    AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
                    AAAAAAAA
                    -----END PRIVATE KEY-----",
                    apnsKeyId: 'BBBBBBBBBB',
                    apnsTopic: 'phpspec.app',
                )
            );
        $this->get(1)->shouldBeAnInstanceOf(PushNotificationConfig::class);
    }

    public function it_should_return_from_cache_2nd_time()
    {
        $this->repositoryMock->get(1)
            ->shouldBeCalledOnce()
            ->willReturn(
                new PushNotificationConfig(
                    apnsTeamId: 'AAAAAAAAAA',
                    apnsKey: "-----BEGIN PRIVATE KEY-----
                    AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
                    AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
                    AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
                    AAAAAAAA
                    -----END PRIVATE KEY-----",
                    apnsKeyId: 'BBBBBBBBBB',
                    apnsTopic: 'phpspec.app',
                )
            );
        $this->get(1)->shouldBeAnInstanceOf(PushNotificationConfig::class);

        $this->get(1)->shouldBeAnInstanceOf(PushNotificationConfig::class);
    }
}
