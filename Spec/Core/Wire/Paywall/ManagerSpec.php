<?php

namespace Spec\Minds\Core\Wire\Paywall;

use Minds\Core\Wire\Paywall\Manager;
use Minds\Entities\Activity;
use Minds\Entities\Image;
use Minds\Entities\Video;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_true_return_if_paywalled(Activity $activity)
    {
        $activity->isPaywall()
            ->willReturn(true);
        $this->isPaywalled($activity)
            ->shouldBe(true);
    }

    public function it_should_false_return_if_not_paywalled(Activity $activity)
    {
        $activity->isPaywall()
            ->willReturn(false);
        $this->isPaywalled($activity)
            ->shouldBe(false);
    }

    public function it_should_support_images_and_videos(Image $image, Video $video)
    {
        $image->isPayWall()
            ->willReturn(true);
        $this->isPaywalled($image)
            ->shouldBe(true);
        $image->isPayWall()
            ->willReturn(false);
        $this->isPaywalled($image)
            ->shouldBe(false);

        $video->isPayWall()
            ->willReturn(true);
        $this->isPaywalled($video)
            ->shouldBe(true);
        $video->isPayWall()
            ->willReturn(false);
        $this->isPaywalled($video)
            ->shouldBe(false);
    }

    public function it_should_return_if_allowed_to_view_paywall(Activity $activity)
    {
        $activity->isPaywall()
            ->willReturn(true);
        $activity->getOwnerEntity()
            ->willReturn(new User);

        $this->setUser(new User)
            ->isAllowed($activity)
            ->shouldBe(true);
    }

    public function it_should_allow_users_to_set_paywall_to_null(Activity $activity)
    {
        $activity->getWireThreshold()
            ->shouldBeCalled()
            ->willReturn(null);

        $activity->setPaywall(false)->shouldBeCalled();
        $this->validateEntity($activity);
    }
}
