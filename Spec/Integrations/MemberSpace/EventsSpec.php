<?php

namespace Spec\Minds\Integrations\MemberSpace;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Config\Config;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Integrations\MemberSpace\Events;
use Minds\Integrations\MemberSpace\MemberSpaceService;
use Minds\Integrations\MemberSpace\Models\MemberSpaceProfile;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class EventsSpec extends ObjectBehavior
{
    private Collaborator $memberSpaceServiceMock;
    public function let(
        EventsDispatcher $eventsDispatcherMock,
        Config $configMock,
        MemberSpaceService $memberSpaceServiceMock,
    ) {
        $this->beConstructedWith($eventsDispatcherMock, $configMock, $memberSpaceServiceMock);
        $this->memberSpaceServiceMock = $memberSpaceServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Events::class);
    }

    public function it_should_not_return_config_if_not_memberspace(Event $eventMock)
    {
        $eventMock->getParameters()->willReturn([
            'provider' => new OidcProvider(1, 'not memberspace', 'https://minds.com', '1', 'cipherText', [])
        ]);

        $eventMock->setResponse()->shouldNotBeCalled();

        $this->getOpenIdConfiguration($eventMock);
    }

    
    public function it_should_return_config_if_memberspace(Event $eventMock)
    {
        $eventMock->getParameters()->willReturn([
            'provider' => new OidcProvider(1, 'memberspace', 'https://minds.memberspace.com', '1', 'cipherText', [])
        ]);

        $eventMock->setResponse([
            "token_endpoint" => "https://minds.memberspace.com/oauth/token",
            "authorization_endpoint" => "https://minds.memberspace.com/oauth/authorize",
            "scopes_supported" => ["read.account"]
        ])->shouldBeCalled();

        $this->getOpenIdConfiguration($eventMock);
    }

    public function it_should_return_scopes(Event $eventMock)
    {
        $eventMock->getParameters()->willReturn([
            'provider' => new OidcProvider(1, 'memberspace', 'https://minds.memberspace.com', '1', 'cipherText', [])
        ]);

        $eventMock->setResponse(['read.account'])->shouldBeCalled();

        $this->getScopes($eventMock);
    }

    public function it_should_return_remote_user(Event $eventMock)
    {
        $eventMock->getParameters()->willReturn([
            'provider' => new OidcProvider(1, 'memberspace', 'https://minds.memberspace.com', '1', 'cipherText', []),
            'oauth_token_response' => [
                'access_token' => 'accessTokenHere'
            ]
        ]);

        $this->memberSpaceServiceMock->getProfile('accessTokenHere')
            ->shouldBeCalled()
            ->willReturn(new MemberSpaceProfile(
                id: 1,
                email: 'test@test.com',
                name: 'test name'
            ));

        $eventMock->setResponse((object) [
            'sub' => 1,
            'given_name' => 'test name',
            'preferred_username' => 'TestN',
            'email' => 'test@test.com',
        ])->shouldBeCalled();

        $this->getRemoteUser($eventMock);
    }
}
