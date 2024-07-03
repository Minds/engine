<?php

namespace Spec\Minds\Core\Blockchain\UnstoppableDomains;

use Minds\Core\Blockchain\UnstoppableDomains\Client;
use Minds\Core\Blockchain\UnstoppableDomains\Controller;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    private $clientMock;

    public function let(Client $client)
    {
        $this->beConstructedWith($client);
        $this->clientMock = $client;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    // public function it_should_return_a_domain(ServerRequest $serverRequest)
    // {
    //     $serverRequest->getAttribute('parameters')
    //         ->willReturn([
    //             'walletAddress' => '0xda730bDa67b84620412b0f26E616803ac213fB3B',
    //         ]);

    //     $this->clientMock->getDomains('0xda730bDa67b84620412b0f26E616803ac213fB3B')
    //         ->willReturn([
    //             'minds.eth'
    //         ]);

    //     $response = $this->getDomains($serverRequest);
    //     $response->getBody()->getContents()->shouldBe(json_encode([
    //         'status' => 'success',
    //         'domains' => [ 'minds.eth' ]
    //     ]));
    // }
}
