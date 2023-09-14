<?php

namespace Spec\Minds\Core\FeedNotices;

use Minds\Core\FeedNotices\Controller;
use Minds\Core\FeedNotices\Manager;
use Minds\Core\FeedNotices\Notices\BoostChannelNotice;
use Minds\Core\FeedNotices\Notices\SetupChannelNotice;
use Minds\Core\FeedNotices\Notices\VerifyEmailNotice;
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

        $user->getName()->willReturn('phpspec');
        $user->isTrusted()->willReturn(true);
        $user->get('briefdescription')->willReturn('');

        $this->manager->getNotices($user)
            ->shouldBeCalled()
            ->willReturn([
                (new BoostChannelNotice)->setUser($user->getWrappedObject()),
                (new VerifyEmailNotice)->setUser($user->getWrappedObject()),
                (new SetupChannelNotice)->setUser($user->getWrappedObject()),
            ]);

        $response = $this->getNotices($request);

        $json = $response->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 'success',
            'notices' => [
                [
                    'key' => 'boost-channel',
                    'location' => 'top',
                    'should_show' => true,
                    'is_dismissible' => true
                ],
                [
                    'key' => 'verify-email',
                    'location' => 'top',
                    'should_show' => false,
                    'is_dismissible' => false
                ],
                [
                    'key' => 'setup-channel',
                    'location' => 'inline',
                    'should_show' => true,
                    'is_dismissible' => true
                ],
            ]
        ]));
    }
}
