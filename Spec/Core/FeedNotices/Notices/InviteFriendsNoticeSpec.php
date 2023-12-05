<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\Config\Config;
use Minds\Core\FeedNotices\Notices\InviteFriendsNotice;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class InviteFriendsNoticeSpec extends ObjectBehavior
{
    private Collaborator $config;

    public function let(
        Config $config
    ) {
        $this->config = $config;
        $this->beConstructedWith($config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(InviteFriendsNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('top');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('invite-friends');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show(
        User $user,
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_this_is_a_tenant_context(
        User $user
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn('123');

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
        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'invite-friends',
            'location' => 'top',
            'should_show' => true,
            'is_dismissible' => true
        ]);
    }
}
