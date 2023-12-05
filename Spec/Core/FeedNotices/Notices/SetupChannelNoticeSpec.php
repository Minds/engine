<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\Config\Config;
use Minds\Core\FeedNotices\Notices\SetupChannelNotice;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class SetupChannelNoticeSpec extends ObjectBehavior
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
        $this->shouldHaveType(SetupChannelNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('setup-channel');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show_because_no_name(
        User $user
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('');

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show_because_no_description(
        User $user
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('123');

        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('');

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_name_and_description_are_set(
        User $user
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $user->getName()
            ->shouldBeCalled()
            ->willReturn('123');
        
        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('321');
    
        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
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
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('123');

        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('321');

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'setup-channel',
            'location' => 'inline',
            'should_show' => false,
            'is_dismissible' => true
        ]);
    }
}
