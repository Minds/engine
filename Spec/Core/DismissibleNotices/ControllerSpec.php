<?php

namespace Spec\Minds\Core\DismissibleNotices;

use Exception;
use Minds\Core\DismissibleNotices\Controller;
use Minds\Core\DismissibleNotices\Manager;
use Zend\Diactoros\ServerRequest;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ControllerSpec extends ObjectBehavior
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
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_set_dismissed_id(ServerRequest $request)
    {
        $request->getAttribute('parameters')
            ->willReturn([
                'id' => 'test-notice-id',
            ]);

        $this->manager->setDismissed('test-notice-id')
                ->shouldBeCalled();

        $response = $this->dismissNotice($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe('{"status":"success"}');
    }

    public function it_should_error_if_no_id(ServerRequest $request)
    {
        $this->manager->setDismissed(Argument::any())
            ->shouldNotBeCalled();

        $response = $this->dismissNotice($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe('{"status":"error","message":":id not provided"}');
    }

    public function it_should_error_if_bad_id(ServerRequest $request)
    {
        $request->getAttribute('parameters')
            ->willReturn([
                'id' => 'test-bad-notice-id',
            ]);

        $this->manager->setDismissed('test-bad-notice-id')
                ->willThrow(new Exception());

        $response = $this->dismissNotice($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe('{"status":"error","message":"Invalid Notice ID provided"}');
    }
}
