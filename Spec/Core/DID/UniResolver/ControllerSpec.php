<?php

namespace Spec\Minds\Core\DID\UniResolver;

use Minds\Core\DID\UniResolver\Controller;
use Minds\Core\DID\UniResolver\Manager;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_return_did_document(Manager $manager, ServerRequest $request)
    {
        $this->beConstructedWith($manager);

        $manager->resolve('did:web:minds.com:mark')
            ->willReturn([]);

        $request->getAttribute('parameters')
            ->willReturn(
                [
                    'did' => 'did:web:minds.com:mark',
                ]
            );

        $jsonResponse = $this->resolve($request);
        $jsonResponse->getBody()->getContents()->shouldBe("[]");
    }
}
