<?php

namespace Spec\Minds\Core\FeedNotices;

use Minds\Core\FeedNotices\Controller;
use Minds\Core\FeedNotices\Manager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    public $manager;

    public function let(
        Manager $manager
    ) {
        $this->manager = $manager;
        $this->beConstructedWith($manager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_get_notices(
        ServerRequest $request,
        User $user
    ) {
        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->manager->getNotices($user)
            ->shouldBeCalled()
            ->willReturn([1, 2, 3]);

        $response = $this->getNotices($request);

        $json = $response->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 'success',
            'notices' => [1, 2, 3]
        ]));
    }
}
