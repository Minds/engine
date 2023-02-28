<?php
namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\BoostLatestPostNotice;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class BoostLatestPostNoticeSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostLatestPostNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('boost-latest-post');
    }

    public function it_should_determine_if_notice_should_show(
        User $user,
    ) {
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
        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'boost-latest-post',
            'location' => 'inline',
            'should_show' => true
        ]);
    }
}
