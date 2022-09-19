<?php

namespace Spec\Minds\Core\Feeds\Activity;

use Minds\Core\Feeds\Activity\Manager;
use Minds\Core\Feeds\Activity\Controller;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    private $managerMock;

    public function let(Manager $managerMock)
    {
        $this->beConstructedWith($managerMock);
        $this->managerMock = $managerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_set_scheduled_post(ServerRequest $serverRequest)
    {
        $serverRequest->getAttribute('_user')->willReturn(new User());
        $serverRequest->getParsedBody()
            ->willReturn([
                'time_created' => strtotime('midnight tomorrow'),
            ]);

        $this->managerMock->add(Argument::that(function ($activity) {
            $activity->guid = '123';

            return $activity->getTimeCreated() === strtotime('midnight tomorrow');
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->createNewActivity($serverRequest);
    }
}
