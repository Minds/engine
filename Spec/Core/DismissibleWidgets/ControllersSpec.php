<?php

namespace Spec\Minds\Core\DismissibleWidgets;

use Minds\Core\DismissibleWidgets\Controllers;
use Minds\Core\DismissibleWidgets\Manager;
use Minds\Core\DismissibleWidgets\InvalidWidgetIDException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ControllersSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    public function let(Manager $manager)
    {
        $this->beConstructedWith($manager);
        $this->manager = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controllers::class);
    }

    public function it_should_set_dimissed_id(ServerRequest $request)
    {
        $request->getAttribute('parameters')
            ->willReturn([
                'id' => 'test-widget-id',
            ]);

        $this->manager->setDimissedId('test-widget-id')
                ->shouldBeCalled();

        $response = $this->putWidget($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe('{"status":"success"}');
    }

    public function it_should_error_if_no_id(ServerRequest $request)
    {
        $this->manager->setDimissedId(Argument::any())
            ->shouldNotBeCalled();

        $response = $this->putWidget($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe('{"status":"error","message":":id not provided"}');
    }

    public function it_should_error_if_bad_id(ServerRequest $request)
    {
        $request->getAttribute('parameters')
            ->willReturn([
                'id' => 'test-bad-widget-id',
            ]);

        $this->manager->setDimissedId('test-bad-widget-id')
                ->willThrow(new InvalidWidgetIDException());

        $response = $this->putWidget($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe('{"status":"error","message":"Invalid ID provided"}');
    }
}
