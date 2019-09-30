<?php

namespace Spec\Minds\Core\Blogs\Delegates;

use Minds\Core\Blogs\Blog;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
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
        $this->shouldHaveType('Minds\Core\Blogs\Delegates\PropagateProperties');
    }

    public function it_should_propagate_changes_to_activity()
    {
        $this->blog->getTitle()->shouldBeCalled()->willReturn('New Title');
        $this->activity->get('title')->shouldBeCalled()->willReturn('Old Title');
        $this->activity->set('title', 'New Title')->shouldBeCalled();

        $this->blog->getBody()->shouldBeCalled()->willReturn('body');
        $this->activity->get('blurb')->shouldBeCalled()->willReturn('body');

        $this->blog->getUrl()->shouldBeCalled()->willReturn('some url');
        $this->activity->getURL()->shouldBeCalled()->willReturn('some url');

        $this->blog->getIconUrl()->shouldBeCalled()->willReturn('some icon url');
        $this->activity->get('thumbnail_src')->shouldBeCalled()->willReturn('some other url');
        $this->activity->set('thumbnail_src', 'some icon url')->shouldBeCalled();

        $this->toActivity($this->blog, $this->activity);
    }
}
