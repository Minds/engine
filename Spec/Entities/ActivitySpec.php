<?php

namespace Spec\Minds\Entities;

use Minds\Core\Di\Di;
use Minds\Core\Wire\Sums;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActivitySpec extends ObjectBehavior
{
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Activity\Manager */
    private $activityManager;

    public function let(EntitiesBuilder $entitiesBuilder, Activity\Manager $activityManager)
    {
        $this->beConstructedWith(null, null, $entitiesBuilder, $activityManager);
        $this->entitiesBuilder = $entitiesBuilder;
        $this->activityManager = $activityManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Entities\Activity');
    }

    public function it_has_wire_totals(Sums $sums)
    {
        $sums->setEntity(Argument::any())
            ->willReturn($sums);
        $sums->getEntity()
            ->willReturn(10);

        Di::_()->bind('Wire\Sums', function ($di) use ($sums) {
            return $sums->getWrappedObject();
        });

        $this->beConstructedWith(null);
        $this->guid = '123';
        $this->getWireTotals()->shouldBeLike([
            'tokens' => 10
        ]);
    }

    public function it_allows_comments()
    {
        $this->getAllowComments()->shouldBe(true);
        $this->setAllowComments(false);
        $this->getAllowComments()->shouldBe(false);
    }

    public function it_should_convert_reminded_blog_to_activity()
    {
        $blog = new \Minds\Core\Blogs\Blog();
        $this->remind_object = [
            'guid' => 456
        ];

        $this->entitiesBuilder->single(456)
            ->willReturn($blog);

        $this->activityManager->createFromEntity($blog)
            ->willReturn(new \Minds\Entities\Activity());

        //

        $remind = $this->getRemind();
    }

    public function it_should_return_true_if_remind()
    {
        $this->remind_object = [
            'guid' => 456,
            'quoted_post' => false,
        ];
        $this->isRemind()->shouldBe(true);
    }

    public function it_should_return_true_if_quoted_post()
    {
        $this->remind_object = [
            'guid' => 456,
            'quoted_post' => true,
        ];
        $this->isQuotedPost()->shouldBe(true);
    }
}
