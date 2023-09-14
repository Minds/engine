<?php
namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\NoGroupsNotice;
use Minds\Core\Groups\Membership as GroupMembershipManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class NoGroupsNoticeSpec extends ObjectBehavior
{
    /** @var GroupMembershipManager */
    protected $groupMembershipManager;

    public function let(
        GroupMembershipManager $groupMembershipManager
    ) {
        $this->groupMembershipManager = $groupMembershipManager;
        $this->beConstructedWith($groupMembershipManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NoGroupsNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('top');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('no-groups');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $this->groupMembershipManager->getGroupGuidsByMember([
                'user_guid' => $user->guid,
                'limit' => 1
            ])
            ->shouldBeCalled()
            ->willReturn([]);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show(
        User $user
    ) {
        $this->groupMembershipManager->getGroupGuidsByMember([
                'user_guid' => $user->guid,
                'limit' => 1
            ])
            ->shouldBeCalled()
            ->willReturn(['123']);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_export(User $user)
    {
        $this->groupMembershipManager->getGroupGuidsByMember([
                'user_guid' => $user->guid,
                'limit' => 1
            ])
            ->shouldBeCalled()
            ->willReturn(['123']);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'no-groups',
            'location' => 'top',
            'should_show' => false,
            'is_dismissible' => false
        ]);
    }
}
