<?php

namespace Spec\Minds\Core\Analytics\PostHog;

use Minds\Core\Analytics\PostHog\PostHogConfig;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\SharedCache;
use Minds\Core\Guid;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use PostHog\Client;
use Prophecy\Argument;

class PostHogServiceSpec extends ObjectBehavior
{
    private Collaborator $postHogClientMock;
    private Collaborator $postHogConfigMock;
    private Collaborator $cacheMock;

    public function let(
        Client $postHogClientMock,
        PostHogConfig $postHogConfigMock,
        SharedCache $cacheMock,
        Config $configMock,
    ) {
        $this->beConstructedWith($postHogClientMock, $postHogConfigMock, $cacheMock, $configMock);
        $this->postHogClientMock = $postHogClientMock;
        $this->postHogConfigMock = $postHogConfigMock;
        $this->cacheMock = $cacheMock;

        $this->cacheMock->withTenantPrefix(false)->willReturn($this->cacheMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PostHogService::class);
    }

    public function it_should_capture_an_event(User $user)
    {
        $userGuid = (string) Guid::build();

        $user->getGuid()->shouldBeCalled()->willReturn($userGuid);
        $user->getUsername()->shouldBeCalled()->willReturn('phpspec');
        $user->getEmail()->shouldBeCalled()->willReturn('phpspec@minds.com');
        $user->getPlusExpires()->shouldBeCalled()->willReturn(strtotime('midnight'));
        $user->getProExpires()->shouldBeCalled()->willReturn(null);
        $user->get('time_created')->shouldBeCalled()->willReturn(strtotime('midnight yesterday'));
        $user->isOptOutAnalytics()->willReturn(false);
        $user->getSource()->willReturn(FederatedEntitySourcesEnum::LOCAL);
        $user->isAdmin()->willReturn(false);


        $this->postHogClientMock->capture(
            [
                'event' => 'phpspec_test',
                'distinctId' => $userGuid,
                'properties' => [
                    'entity_guid' => '123',
                    'environment' => 'development',
                    '$set' => [
                        'guid' => $userGuid,
                        'username' => 'phpspec',
                        'email' => 'phpspec@minds.com',
                        'plus_expires' => date('c', strtotime('midnight')),
                        'environment' => 'development',
                        'is_admin' => false,
                    ],
                    '$set_once' => [
                        'joined_timestamp' => date('c', strtotime('midnight yesterday')),
                    ]
                ],
            ]
        )->willReturn(true);

        $this->postHogClientMock->flush()
            ->willReturn(true);

        $this->capture(
            event: 'phpspec_test',
            user: $user,
            properties: [
                'entity_guid' => '123',
            ]
        )
        ->shouldBe(true);
    }

    public function it_should_not_send_if_a_user_is_opted_out(User $user)
    {
        $user->isOptOutAnalytics()->willReturn(true);

        $this->postHogClientMock->capture(Argument::any())
            ->shouldNotBeCalled();

        $this->capture(
            event: 'phpspec_test',
            user: $user,
            properties: [
                'entity_guid' => '123',
            ]
        )
        ->shouldBe(false);
    }

    public function it_should_not_send_if_a_user_is_activitypub(User $user)
    {
        $user->isOptOutAnalytics()->willReturn(false);
        $user->getSource()->willReturn(FederatedEntitySourcesEnum::ACTIVITY_PUB);

        $this->postHogClientMock->capture(Argument::any())
            ->shouldNotBeCalled();

        $this->capture(
            event: 'phpspec_test',
            user: $user,
            properties: [
                'entity_guid' => '123',
            ]
        )
        ->shouldBe(false);
    }

    public function it_should_return_feature_flags_without_cache(User $userMock)
    {
        $this->postHogConfigMock->getApiKey()
            ->willReturn('phpspec');

        $this->postHogConfigMock->getPersonalApiKey()
            ->willReturn('abc');


        $this->cacheMock->has(Argument::any())
            ->shouldNotBeCalled();

        $this->postHogClientMock->loadFlags()
            ->shouldBeCalled();

        $this->postHogClientMock->featureFlags = [];

        $this->cacheMock->set(Argument::any(), Argument::type('array'))
            ->shouldBeCalled();

        $userMock->getGuid()->willReturn('123');

        $this->postHogClientMock->getAllFlags('123', [], [
            'environment' => 'development',
        ], [], true)
            ->willReturn([
                'flag-1' => true,
                'flag-2' => false,
            ]);

        $this->getFeatureFlags(user: $userMock, useCache: false)
            ->shouldBe([
                'flag-1' => true,
                'flag-2' => false,
            ]);
    }

    public function it_should_return_feature_flags_from_cache(User $userMock)
    {
        $this->postHogConfigMock->getApiKey()
            ->willReturn('phpspec');

        $this->cacheMock->has(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cacheMock->get(Argument::any())
            ->shouldBeCalled()
            ->willReturn([]);

        $this->postHogClientMock->loadFlags()
            ->shouldNotBeCalled();

        $this->cacheMock->set(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $userMock->getGuid()->willReturn('123');

        $this->postHogClientMock->getAllFlags('123', [], [
            'environment' => 'development',
        ], [], true)
            ->willReturn([
                'flag-1' => true,
                'flag-2' => false,
            ]);

        $this->getFeatureFlags(user: $userMock, useCache: true)
            ->shouldBe([
                'flag-1' => true,
                'flag-2' => false,
            ]);
    }
}
