<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Core\Nostr\Controller;
use Minds\Core\Nostr\Manager;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_return_nip05_response(Manager $manager, ServerRequest $request)
    {
        $request->getQueryParams()
            ->willReturn([
                'name' => 'mark'
            ]);

        $this->beConstructedWith($manager);

        $manager->getPublicKeyFromUsername('mark')
            ->willReturn('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');
        
        $response = $this->resolveNip05($request);
        $response->getBody()->getContents()->shouldBe(
            json_encode([
                'names' => [
                    'mark' => '4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715',
                ]
            ])
        );
    }
}
