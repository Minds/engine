<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Boost\Network\ElasticRepository;
use Minds\Core\Boost\Network\Iterator;
use Minds\Core\Boost\Network\Manager;
use Minds\Core\EntitiesBuilder;
use PhpSpec\ObjectBehavior;

class IteratorSpec extends ObjectBehavior
{
    /** @var ElasticRepository */
    protected $elasticRepository;
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;
    /** @var Manager */
    protected $manager;

    public function let(ElasticRepository $elasticRepository, EntitiesBuilder $entitiesBuilder, Manager $manager)
    {
        $this->beConstructedWith($elasticRepository, $entitiesBuilder, $manager);

        $this->elasticRepository = $elasticRepository;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->manager = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Iterator::class);
    }

    public function it_should_enforce_max_limit()
    {
        $this->setLimit(Iterator::MAX_LIMIT + 10);
        $this->getLimit()->shouldBe(Iterator::MAX_LIMIT);
    }

    public function it_should_only_set_valid_type()
    {
        // Default
        $this->getType()->shouldBe(Boost::TYPE_NEWSFEED);

        $this->setType(Boost::TYPE_CONTENT);
        $this->getType()->shouldBe(Boost::TYPE_CONTENT);

        $this->setType('UnknownType');
        $this->getType()->shouldBe(Boost::TYPE_CONTENT);
    }

    public function it_should_fetch_number_of_boosts_requested()
    {
        $this->setLimit(3);
        $this->setHydrate(false);

        $boost1 = new Boost();
        $boost1->setGuid(1234);
        $boost1->setCreatedTimestamp(111111);
        $boost2 = new Boost();
        $boost2->setGuid(3456);
        $boost2->setCreatedTimestamp(222222);

        $response1 = new Response();
        $response1[] = $boost1;
        $response1[] = $boost2;
        $response1->setPagingToken(123456789);

        $boost3 = new Boost();
        $boost3->setGuid(5678);
        $boost3->setCreatedTimestamp(333333);
        $boost4 = new Boost();
        $boost4->setGuid(7890);
        $boost4->setCreatedTimestamp(444444);

        $response2 = new Response();
        $response2[] = $boost3;
        $response2[] = $boost4;
        $response2->setPagingToken(0);

        $opts1 = [
            'type' => 'newsfeed',
            'limit' => 3,
            'offset' => 0,
            'state' => 'approved',
            'rating' => 1
        ];

        $opts2 = [
            'type' => 'newsfeed',
            'limit' => 3,
            'offset' => 222222,
            'state' => 'approved',
            'rating' => 1
        ];

        $this->elasticRepository->getList($opts1)->shouldBeCalled()->willReturn($response1);
        $this->elasticRepository->getList($opts2)->shouldBeCalled()->willReturn($response2);

        $this->rewind(); // Simulate start of a foreach

        $this->count()->shouldBe(3); // Even though repo returned 4
        $this->getOffset()->shouldBe(333333); // Timestamp of last in set
    }

    // TODO: Add a test for blocked boost in set
}
