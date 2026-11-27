<?php

namespace Spec\Minds\Core\ActivityPub\Factories;

use GuzzleHttp\Psr7\Response;
use Minds\Core\ActivityPub\Client;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\InMemoryCache;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ActorFactorySpec extends ObjectBehavior
{
    private Collaborator $managerMock;
    private Collaborator $clientMock;

    public function let(
        Manager $managerMock,
        Client $clientMock,
        Config $configMock,
        InMemoryCache $cacheMock,
    ) {
        $this->beConstructedWith($managerMock, $clientMock, $configMock, $cacheMock);
        $this->managerMock = $managerMock;
        $this->clientMock = $clientMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ActorFactory::class);
    }

    public function it_should_refuse_a_document_whose_id_is_on_another_host()
    {
        $uri = 'https://attacker.example/users/x';

        $this->managerMock->isLocalUri($uri)->willReturn(false);

        $this->clientMock->request('GET', $uri)
            ->willReturn(new Response(200, [], json_encode([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'type' => 'Person',
                'id' => 'https://www.minds.com/api/activitypub/users/123',
                'preferredUsername' => 'mark',
                'inbox' => 'https://attacker.example/inbox',
                'outbox' => 'https://attacker.example/outbox',
            ])));

        $this->shouldThrow(UserErrorException::class)->duringFromUri($uri);
    }

    public function it_should_refuse_a_document_without_an_id()
    {
        $uri = 'https://remote.example/users/x';

        $this->managerMock->isLocalUri($uri)->willReturn(false);

        $this->clientMock->request('GET', $uri)
            ->willReturn(new Response(200, [], json_encode([
                'type' => 'Person',
                'preferredUsername' => 'x',
            ])));

        $this->shouldThrow(UserErrorException::class)->duringFromUri($uri);
    }
}
