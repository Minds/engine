<?php

namespace Spec\Minds\Core\DID;

use Minds\Core\DID\Manager;
use Minds\Core\DID\Controller;
use Minds\Core\DID\DIDDocument;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class ControllerSpec extends ObjectBehavior
{
    protected $manager;

    public function let(Manager $manager)
    {
        $this->beConstructedWith($manager);
        $this->manager = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_return_root_document(ServerRequest $request)
    {
        $uri = new Uri('https://minds.local/.well-known/did.json');
        $request->getUri()
            ->willReturn($uri);

        $this->manager->getRootDocument()
            ->shouldBeCalled()
            ->willReturn(new DIDDocument());

        $this->getDIDDocument($request);
    }

    public function it_should_return_user_document(ServerRequest $request)
    {
        $uri = new Uri('https://minds.local/phpspec/did.json');
        $request->getUri()
            ->willReturn($uri);

        $this->manager->getUserDocument('phpspec')
            ->shouldBeCalled()
            ->willReturn(new DIDDocument());

        $this->getDIDDocument($request);
    }
}
