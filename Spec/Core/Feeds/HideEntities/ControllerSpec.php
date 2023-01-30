<?php

namespace Spec\Minds\Core\Feeds\HideEntities;

use Minds\Core\Feeds\HideEntities\Manager;
use Minds\Core\Feeds\HideEntities\Controller;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $managerMock;

    public function let(Manager $managerMock)
    {
        $this->beConstructedWith($managerMock);
        $this->managerMock = $managerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_return_201_status(ServerRequest $serverRequest, User $user)
    {
        $serverRequest->getAttribute('_user')->willReturn($user);
        $serverRequest->getAttribute('parameters')->willReturn([
            'entityGuid' => '1234'
        ]);

        $this->managerMock->withUser($user)
            ->willReturn($this->managerMock);

        $this->managerMock->hideEntityByGuid('1234')
            ->willReturn(true);
    
        $this->hideEntity($serverRequest)
            ->getStatusCode()->shouldBe(201);
    }
}
