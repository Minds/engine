<?php

namespace Spec\Minds\Core\DID\UniResolver;

use Minds\Core\DID\UniResolver\Manager;
use Minds\Core\DID\UniResolver\Client;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_resolve_did(Client $client)
    {
        $this->beConstructedWith($client);

        $client->request('/1.0/identifiers/did:web:minds.com:mark')
            ->willReturn([]);

        $this->resolve('did:web:minds.com:mark')->shouldBe([]);
    }
}
