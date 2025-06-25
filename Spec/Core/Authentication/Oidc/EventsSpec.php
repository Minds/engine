<?php

namespace Spec\Minds\Core\Authentication\Oidc;

use GuzzleHttp\Client;
use Minds\Core\Authentication\Oidc\Events;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Config\Config;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Router\Exceptions\ForbiddenException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Zend\Diactoros\Response\JsonResponse;

class EventsSpec extends ObjectBehavior
{
    private Collaborator $eventsDispatcherMock;
    private Collaborator $configMock;
    private Collaborator $httpClientMock;
    
    public function let(
        EventsDispatcher $eventsDispatcherMock,
        Config $configMock,
        Client $httpClientMock,
    ) {
        $this->beConstructedWith($eventsDispatcherMock, $configMock, $httpClientMock);
        $this->eventsDispatcherMock = $eventsDispatcherMock;
        $this->configMock = $configMock;
        $this->httpClientMock = $httpClientMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Events::class);
    }

    public function it_should_return_remote_discord_user(Event $event)
    {
        $event->getParameters()
            ->willReturn([
                'provider' => new OidcProvider(
                    id: 1,
                    name: 'PHPSpec',
                    issuer: 'https://discord.com',
                    clientId: 'abc',
                    clientSecretCipherText: 'redacted',
                    configs: []
                ),
                'openid_configuration' => [
                    'userinfo_endpoint' => 'https://discord.com/api/userinfo',
                ],
                'oauth_token_response' => [
                    'access_token' => 'ACCESS_TOKEN',
                ]
            ]);

        $this->httpClientMock->get('https://discord.com/api/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ACCESS_TOKEN',
            ]
        ])->shouldBeCalled()
        ->willReturn(new JsonResponse([
            'sub' => 'sub-1',
            'preferred_username' => 'username',
        ]));

        $event->setResponse((object) [
            'sub' => 'sub-1',
            'preferred_username' => 'username',
        ])->shouldBeCalled();

        //
        $this->getRemoteUser($event);
    }

    public function it_should_return_remote_discord_user_when_server_id_restricted(Event $event)
    {
        $event->getParameters()
            ->willReturn([
                'provider' => new OidcProvider(
                    id: 1,
                    name: 'PHPSpec',
                    issuer: 'https://discord.com',
                    clientId: 'abc',
                    clientSecretCipherText: 'redacted',
                    configs: [
                        'server_id' => 'server-1'
                    ]
                ),
                'openid_configuration' => [
                    'userinfo_endpoint' => 'https://discord.com/api/userinfo',
                ],
                'oauth_token_response' => [
                    'access_token' => 'ACCESS_TOKEN',
                ]
            ]);

        $this->httpClientMock->get('https://discord.com/api/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ACCESS_TOKEN',
            ]
        ])->shouldBeCalled()
        ->willReturn(new JsonResponse([
            'sub' => 'sub-1',
            'preferred_username' => 'username',
        ]));

        $event->setResponse((object) [
            'sub' => 'sub-1',
            'preferred_username' => 'username',
        ])->shouldBeCalled();

        $this->httpClientMock->get('https://discord.com/api/users/@me/guilds', [
            'headers' => [
                'Authorization' => 'Bearer ACCESS_TOKEN',
            ]
        ])->shouldBeCalled()
        ->willReturn(new JsonResponse([
            [
                'id' => 'server-1'
            ]
        ]));

        //
        $this->getRemoteUser($event);
    }

    public function it_should_throw_exception_when_server_id_restricted(Event $event)
    {
        $event->getParameters()
            ->willReturn([
                'provider' => new OidcProvider(
                    id: 1,
                    name: 'PHPSpec',
                    issuer: 'https://discord.com',
                    clientId: 'abc',
                    clientSecretCipherText: 'redacted',
                    configs: [
                        'server_id' => 'server-1'
                    ]
                ),
                'openid_configuration' => [
                    'userinfo_endpoint' => 'https://discord.com/api/userinfo',
                ],
                'oauth_token_response' => [
                    'access_token' => 'ACCESS_TOKEN',
                ]
            ]);

        $this->httpClientMock->get('https://discord.com/api/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ACCESS_TOKEN',
            ]
        ])->shouldBeCalled()
        ->willReturn(new JsonResponse([
            'sub' => 'sub-1',
            'preferred_username' => 'username',
        ]));

        $event->setResponse((object) [
            'sub' => 'sub-1',
            'preferred_username' => 'username',
        ])->shouldNotBeCalled();

        $this->httpClientMock->get('https://discord.com/api/users/@me/guilds', [
            'headers' => [
                'Authorization' => 'Bearer ACCESS_TOKEN',
            ]
        ])->shouldBeCalled()
        ->willReturn(new JsonResponse([
            [
                'id' => 'server-2'
            ]
        ]));

        //
        $this->shouldThrow(ForbiddenException::class)->duringGetRemoteUser($event);
    }
}
