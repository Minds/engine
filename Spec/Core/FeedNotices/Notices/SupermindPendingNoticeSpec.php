<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Common\Repository\Response;
use Minds\Core\FeedNotices\Notices\SupermindPendingNotice;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class SupermindPendingNoticeSpec extends ObjectBehavior
{
    /** @var SupermindManager */
    protected $supermindManager;

    public function let(
        SupermindManager $supermindManager
    ) {
        $this->supermindManager = $supermindManager;
        $this->beConstructedWith($supermindManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindPendingNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('top');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('supermind-pending');
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $this->supermindManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->supermindManager);

        $this->supermindManager->countReceivedRequests(
            SupermindRequestStatus::CREATED
        )
            ->shouldBeCalled()
            ->willReturn(1);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_no_request_is_found(
        User $user
    ) {
        $this->supermindManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->supermindManager);

        $this->supermindManager->countReceivedRequests(
            SupermindRequestStatus::CREATED
        )
            ->shouldBeCalled()
            ->willReturn(0);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_export(
        User $user
    ) {
        $this->supermindManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->supermindManager);

        $this->supermindManager->countReceivedRequests(
            SupermindRequestStatus::CREATED
        )
            ->shouldBeCalled()
            ->willReturn(1);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'supermind-pending',
            'location' => 'top',
            'should_show' => true
        ]);
    }
}
