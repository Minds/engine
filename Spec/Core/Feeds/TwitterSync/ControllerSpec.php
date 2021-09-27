<?php

namespace Spec\Minds\Core\Feeds\TwitterSync;

use Minds\Core\Feeds\TwitterSync\ConnectedAccount;
use Minds\Core\Feeds\TwitterSync\Controller;
use Minds\Core\Feeds\TwitterSync\Manager;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

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

    public function it_should_get_connected_account(ServerRequest $request, User $user, ConnectedAccount $connectedAccount)
    {
        $request->getAttribute('_user')->willReturn($user);

        $this->manager->getConnectedAccountByUser($user)
            ->willReturn($connectedAccount);

        $connectedAccount->export()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->getConnectedAccount($request);
    }

    public function it_should_throw_404_if_not_found(ServerRequest $request, User $user, ConnectedAccount $connectedAccount)
    {
        $request->getAttribute('_user')->willReturn($user);

        $this->manager->getConnectedAccountByUser($user)
            ->willThrow(NotFoundException::class);

        $this->shouldThrow(NotFoundException::class)->duringGetConnectedAccount($request);
    }
}
