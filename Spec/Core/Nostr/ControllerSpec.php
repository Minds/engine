<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Core\Nostr\Controller;
use Minds\Core\Nostr\EntityImporter;
use Minds\Core\Nostr\Manager;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    private Collaborator $managerMock;

    public function let(Manager $managerMock, EntityImporter $entityImporterMock)
    {
        $this->beConstructedWith($managerMock, null, $entityImporterMock);

        $this->managerMock = $managerMock;
    }

    // public function it_is_initializable()
    // {
    //     $this->shouldHaveType(Controller::class);
    // }

    // public function it_should_return_nip05_and_nip20_response(ServerRequest $request)
    // {
    //     $request->getQueryParams()
    //         ->willReturn([
    //             'name' => 'mark'
    //         ]);

    //     $this->managerMock->getPublicKeyFromUsername('mark')
    //         ->willReturn('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');

    //     $this->managerMock->getDomain()
    //         ->willReturn('minds.com');

    //     $response = $this->resolveNip05($request);
    //     $response->getBody()->getContents()->shouldBe(
    //         json_encode([
    //             'names' => [
    //                 'mark' => '4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715',
    //             ],
    //             'relays' => [
    //                 '4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715' => [ 'wss://relay.minds.com/nostr/v1/ws' ]
    //             ]
    //         ], JSON_UNESCAPED_SLASHES)
    //     );
    // }
}
