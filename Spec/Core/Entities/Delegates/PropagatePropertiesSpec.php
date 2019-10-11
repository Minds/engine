<?php

namespace Spec\Minds\Core\Entities\Delegates;

use Minds\Core\Blogs\Blog;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;

class PropagatePropertiesSpec extends ObjectBehavior
{
    /** @var Blog */
    protected $blog;
    /** @var Activity */
    protected $activity;

    public function let(
        Blog $blog,
        Activity $activity
    ) {
        $this->blog = $blog;
        $this->activity = $activity;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Entities\Delegates\PropagateProperties');
    }

    public function it_should_propagate_changes_to_activity()
    {
        $this->blog->getNsfw()->shouldBeCalled()->willReturn([1]);
        $this->activity->getNsfw()->shouldBeCalled()->willReturn([]);
        $this->activity->setNsfw([1])->shouldBeCalled();

        $this->blog->getNsfwLock()->shouldBeCalled()->willReturn([1]);
        $this->activity->getNsfwLock()->shouldBeCalled()->willReturn([]);
        $this->activity->setNsfwLock([1])->shouldBeCalled();

        $this->toActivity($this->blog, $this->activity);
    }

    public function it_should_propogate_properties_from_activity()
    {
        $this->activity->getNsfw()->shouldBeCalled()->willReturn([1]);
        $this->blog->getNsfw()->shouldBeCalled()->willReturn([]);
        $this->blog->setNsfw([1])->shouldBeCalled();

        $this->activity->getNsfwLock()->shouldBeCalled()->willReturn([1]);
        $this->blog->getNsfwLock()->shouldBeCalled()->willReturn([]);
        $this->blog->setNsfwLock([1])->shouldBeCalled();

        $this->fromActivity($this->activity, $this->blog);
    }
}
