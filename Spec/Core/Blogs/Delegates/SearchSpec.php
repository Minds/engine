<?php

namespace Spec\Minds\Core\Blogs\Delegates;

use Minds\Core\Blogs\Blog;
use Minds\Core\Blogs\Delegates\Search;
use Minds\Core\Events\EventsDispatcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SearchSpec extends ObjectBehavior
{
    /**
     * @var EventsDispatcher
     */
    protected $eventsDispatcher;

    public function let(
        EventsDispatcher $eventsDispatcher
    ) {
        $this->beConstructedWith($eventsDispatcher);
        $this->eventsDispatcher = $eventsDispatcher;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Search::class);
    }

    public function it_should_react_to_index(Blog $blog)
    {
        $this->eventsDispatcher->trigger('search:index', 'object:blog', [
            'entity' => $blog,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->shouldNotThrow(\Exception::class)
            ->duringIndex($blog);
    }

    public function it_should_react_to_prune(Blog $blog)
    {
        $this->eventsDispatcher->trigger('search:cleanup', 'object:blog', [
            'entity' => $blog,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->shouldNotThrow(\Exception::class)
            ->duringPrune($blog);
    }
}
