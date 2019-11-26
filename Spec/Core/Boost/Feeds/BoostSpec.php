<?php

namespace Spec\Minds\Core\Boost\Feeds;

use Minds\Common\Urn;
use Minds\Core\Boost\Feeds\Boost;
use Minds\Core\Boost\Network\Iterator;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Entities\Resolver;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Helpers\Time;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Boost\Network\Boost as BoostObj;

class BoostSpec extends ObjectBehavior
{
    protected $user;
    protected $resolver;
    protected $cacher;
    protected $iterator;

    public function let(User $user, Resolver $resolver, abstractCacher $cacher, Iterator $iterator)
    {
        $this->beConstructedWith($user, $resolver, $cacher);
        $this->user = $user;
        $this->resolver = $resolver;
        $this->cacher = $cacher;
        $this->iterator = $iterator;
        $this->setMockIterator($iterator);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Boost::class);
    }

    public function it_should_get_boosts(BoostObj $boost1, BoostObj $boost2)
    {
        $params = [
            'limit' => 10,
            'offset' => 10,
            'rating' => 0,
            'quality' => 0
        ];

        $this->user->getGUID()->shouldBeCalled()->willReturn(1234);
        $this->user->get('guid')->shouldBeCalled()->willReturn(1234);
        $this->user->getTimeCreated()->shouldBeCalled()->willReturn(time() - Time::TWO_HOUR);
        $this->user->getBoostRating()->shouldBeCalled()->willReturn(0);

        $this->iterator->setLimit(10)->shouldBeCalled()->willReturn($this->iterator);
        $this->iterator->setOffset(10)->shouldBeCalled()->willReturn($this->iterator);
        $this->iterator->setRating(0)->shouldBeCalled()->willReturn($this->iterator);
        $this->iterator->setQuality(0)->shouldBeCalled()->willReturn($this->iterator);
        $this->iterator->setType('newsfeed')->shouldBeCalled()->willReturn($this->iterator);
        $this->iterator->setHydrate(false)->shouldBeCalled()->willReturn($this->iterator);
        $this->iterator->setUserGuid(1234)->shouldBeCalled()->willReturn($this->iterator);

        $this->iterator->rewind()->shouldBeCalled();
        $this->iterator->valid()->shouldBeCalled()->willReturn(true, true, false);
        $this->iterator->current()->shouldBeCalled()->willReturn($boost1, $boost2);
        $this->iterator->next()->shouldBeCalled();
        $this->iterator->getOffset()->shouldBeCalled()->willReturn($params['offset']);

        $boost1->getCreatedTimestamp()->shouldBeCalled()->willReturn(1571749729);
        $boost1->getGuid()->shouldBeCalled()->willReturn(1234);
        $boost1->getOwnerGuid()->shouldBeCalled()->willReturn(5678);
        $boost1->getType()->shouldBeCalled()->willReturn('newsfeed');

        $this->resolver->single(Argument::type(Urn::class))->shouldBeCalled()->willReturn(new Entity());

        $boost2->getCreatedTimestamp()->shouldBeCalled()->willReturn(1571750627);
        $boost2->getGuid()->shouldBeCalled()->willReturn(3456);
        $boost2->getOwnerGuid()->shouldBeCalled()->willReturn(5678);
        $boost2->getType()->shouldBeCalled()->willReturn('newsfeed');

        $this->setLimit($params['limit'])->shouldReturn($this);
        $this->setOffset($params['offset'])->shouldReturn($this);
        $this->setRating(0)->shouldReturn($this);

        $this->get()->shouldBeArray();
    }
}
