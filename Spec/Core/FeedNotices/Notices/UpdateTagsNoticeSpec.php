<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\UpdateTagsNotice;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class UpdateTagsNoticeSpec extends ObjectBehavior
{
    /** @var UserHashtagsManager */
    protected $userHashtagsManager;

    public function let(
        UserHashtagsManager $userHashtagsManager
    ) {
        $this->userHashtagsManager = $userHashtagsManager;
        $this->beConstructedWith($userHashtagsManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UpdateTagsNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('update-tags');
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->hasSetHashtags()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show(
        User $user
    ) {
        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->hasSetHashtags()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_export(User $user)
    {
        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->hasSetHashtags()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'update-tags',
            'location' => 'inline',
            'should_show' => false
        ]);
    }
}
