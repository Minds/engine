<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\BuildYourAlgorithmNotice;
use Minds\Core\SocialCompass\Manager as SocialCompassManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class BuildYourAlgorithmNoticeSpec extends ObjectBehavior
{
    /** @var SocialCompassManager */
    protected $socialCompassManager;

    public function let(
        SocialCompassManager $socialCompassManager
    ) {
        $this->socialCompassManager = $socialCompassManager;
        $this->beConstructedWith($socialCompassManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BuildYourAlgorithmNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('build-your-algorithm');
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $this->socialCompassManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->socialCompassManager);

        $this->socialCompassManager->countAnswers()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show(
        User $user
    ) {
        $this->socialCompassManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->socialCompassManager);

        $this->socialCompassManager->countAnswers()
            ->shouldBeCalled()
            ->willReturn(1);

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
        $this->socialCompassManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->socialCompassManager);

        $this->socialCompassManager->countAnswers()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'build-your-algorithm',
            'location' => 'inline',
            'should_show' => true
        ]);
    }
}
