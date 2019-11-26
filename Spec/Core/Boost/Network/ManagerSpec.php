<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Boost\Network\ElasticRepository;
use Minds\Core\Boost\Network\Manager;
use Minds\Core\Boost\Network\CassandraRepository;
use Minds\Core\EntitiesBuilder;
use Minds\Core\GuidBuilder;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Di\Di;

class ManagerSpec extends ObjectBehavior
{
    /** @var CassandraRepository */
    private $cassandraRepository;
    /** @var ElasticRepository */
    private $elasticRepository;
    /** @var EntitiesBuilder */
    private $entitiesBuilder;
    /** @var GuidBuilder */
    private $guidBuilder;

    public function let(
        CassandraRepository $cassandraRepository,
        ElasticRepository $elasticRepository,
        EntitiesBuilder $entitiesBuilder,
        GuidBuilder $guidBuilder
    ) {
        $this->beConstructedWith($cassandraRepository, $elasticRepository, $entitiesBuilder, $guidBuilder);
        $this->cassandraRepository = $cassandraRepository;
        $this->elasticRepository = $elasticRepository;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->guidBuilder = $guidBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_a_list_of_boosts_to_review()
    {
        $responseObj = new Response([
            (new Boost)
                ->setGuid(1)
                ->setEntityGuid(123)
                ->setImpressions(1000)
                ->setOwnerGuid(1),
            (new Boost)
                ->setGuid(2)
                ->setEntityGuid(456)
                ->setImpressions(100)
                ->setOwnerGuid(2)
        ]);

        $this->elasticRepository->getList([
            'state' => Manager::OPT_STATEQUERY_REVIEW,
            'useElastic' => true,
        ])
            ->shouldBeCalled()
            ->willReturn($responseObj);

        $this->cassandraRepository->getList([
            'state' => Manager::OPT_STATEQUERY_REVIEW,
            'useElastic' => true,
            'guids' => [1, 2],
        ])
            ->shouldBeCalled()
            ->willReturn($responseObj);

        $this->entitiesBuilder->single(123)->shouldBeCalled()->willReturn((new Activity)->set('guid', 123));
        $this->entitiesBuilder->single(1)->shouldBeCalled()->willReturn((new User)->set('guid', 1));
        $this->entitiesBuilder->single(456)->shouldBeCalled()->willReturn((new Activity)->set('guid', 456));
        $this->entitiesBuilder->single(2)->shouldBeCalled()->willReturn((new User)->set('guid', 2));

        $response = $this->getList([
            'state' => Manager::OPT_STATEQUERY_REVIEW,
        ]);

        $response[0]->getEntity()->getGuid()->shouldBe(123);
        $response[0]->getOwner()->getGuid()->shouldBe(1);
        $response[0]->getImpressions()->shouldBe(1000);

        $response[1]->getEntity()->getGuid()->shouldBe(456);
        $response[1]->getOwner()->getGuid()->shouldBe(2);
        $response[1]->getImpressions()->shouldBe(100);
    }

    public function it_should_return_a_list_of_boosts_to_deliver()
    {
        $responseObj = new Response([
            (new Boost)
                ->setEntityGuid(123)
                ->setImpressions(1000)
                ->setOwnerGuid(1),
            (new Boost)
                ->setEntityGuid(456)
                ->setImpressions(100)
                ->setOwnerGuid(2)
        ]);

        $this->elasticRepository->getList([
            'state' => Manager::OPT_STATEQUERY_APPROVED,
            'useElastic' => true,
        ])->shouldBeCalled()->willReturn($responseObj);

        $this->entitiesBuilder->single(123)->shouldBeCalled()->willReturn((new Activity)->set('guid', 123));
        $this->entitiesBuilder->single(1)->shouldBeCalled()->willReturn((new User)->set('guid', 1));
        $this->entitiesBuilder->single(456)->shouldBeCalled()->willReturn((new Activity)->set('guid', 456));
        $this->entitiesBuilder->single(2)->shouldBeCalled()->willReturn((new User)->set('guid', 2));

        $response = $this->getList([
            'state' => Manager::OPT_STATEQUERY_APPROVED,
            'useElastic' => true,
        ]);

        $response[0]->getEntity()->getGuid()->shouldBe(123);
        $response[0]->getOwner()->getGuid()->shouldBe(1);
        $response[0]->getImpressions()->shouldBe(1000);

        $response[1]->getEntity()->getGuid()->shouldBe(456);
        $response[1]->getOwner()->getGuid()->shouldBe(2);
        $response[1]->getImpressions()->shouldBe(100);
    }

    public function it_should_return_a_list_of_boosts_from_guids()
    {
        $response = new Response([
            (new Boost)
                ->setEntityGuid(123)
                ->setImpressions(1000)
                ->setOwnerGuid(1),
            (new Boost)
                ->setEntityGuid(456)
                ->setImpressions(100)
                ->setOwnerGuid(2)
        ]);

        $this->cassandraRepository->getList([
            'state' => null,
            'guids' => [123, 456],
            'useElastic' => false,
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->entitiesBuilder->single(123)->shouldBeCalled()->willReturn((new Activity)->set('guid', 123));
        $this->entitiesBuilder->single(1)->shouldBeCalled()->willReturn((new User)->set('guid', 1));
        $this->entitiesBuilder->single(456)->shouldBeCalled()->willReturn((new Activity)->set('guid', 456));
        $this->entitiesBuilder->single(2)->shouldBeCalled()->willReturn((new User)->set('guid', 2));

        $response = $this->getList([
            'guids' => [123, 456],
        ]);

        $response[0]->getEntity()->getGuid()->shouldBe(123);
        $response[0]->getOwner()->getGuid()->shouldBe(1);
        $response[0]->getImpressions()->shouldBe(1000);

        $response[1]->getEntity()->getGuid()->shouldBe(456);
        $response[1]->getOwner()->getGuid()->shouldBe(2);
        $response[1]->getImpressions()->shouldBe(100);
    }

    public function it_should_add_a_boost(Boost $boost)
    {
        $this->guidBuilder->build()->shouldBeCalled()->willReturn(1);

        $boost->getGuid()->shouldbeCalled()->willReturn(null);
        $boost->setGuid(1)->shouldBeCalled();

        $this->cassandraRepository->add($boost)->shouldBeCalled();
        $this->elasticRepository->add($boost)->shouldBeCalled();

        $this->add($boost)->shouldReturn(true);
    }

    public function it_should_update_a_boost(Boost $boost)
    {
        $this->cassandraRepository->update($boost, ['@timestamp'])->shouldBeCalled();
        $this->elasticRepository->update($boost, ['@timestamp'])->shouldBeCalled();

        $this->update($boost, ['@timestamp']);
    }

    public function it_should_resync_a_boost_on_elasticsearch(Boost $boost)
    {
        $this->elasticRepository->update($boost, ['@timestamp'])->shouldBeCalled();

        $this->resync($boost, ['@timestamp']);
    }

    public function it_should_check_if_the_entity_was_already_boosted(Boost $boost, Response $response)
    {
        $params = [
            'useElastic' => true,
            'state' => 'review',
            'type' => 'newsfeed',
            'entity_guid' => '1234',
            'limit' => 1
        ];

        $this->elasticRepository->getList($params)->shouldBeCalled()->willReturn($response);
        $response->toArray()->shouldbeCalled()->willReturn([$boost]);
        $this->cassandraRepository->getList(Argument::any())->shouldBeCalled()->willReturn($response);

        $boost->getGuid()->shouldBeCalled()->willReturn('9012');
        $boost->getType()->shouldBeCalled()->willReturn('newsfeed');
        $boost->getEntityGuid()->shouldBeCalled()->willReturn('1234');
        $boost->getOwnerGuid()->shouldBeCalled()->willReturn(5678);

        $response->getPagingToken()->shouldBeCalled()->willReturn(1571060432);
        $response->setPagingToken(1571060432)->shouldBeCalled();

        $response->rewind()->shouldBeCalled();
        $response->valid()->shouldBeCalled()->willReturn(true, false);
        $response->current()->shouldBeCalled()->willReturn($boost);
        $response->key()->shouldBeCalled()->willReturn(0);


        $boost->setEntity(null)->shouldBeCalled(); // TODO: Expand with entitiesBuilder mock call
        $boost->setOwner(null)->shouldBeCalled(); // TODO: Expand with entitiesBuilder mock call
        $boost->getEntity()->shouldBeCalled()->willReturn(null);
        $boost->setEntity(Argument::type(Entity::class))->shouldBeCalled();

        $response->next()->shouldBeCalled();
        $response->count()->shouldBeCalled()->willReturn(1);

        $this->checkExisting($boost)->shouldReturn(true);
    }

    public function it_should_request_offchain_boosts(Boost $boost, Response $response)
    {
        $params = [
            'useElastic' => true,
            'state' => 'active',
            'type' => 'newsfeed',
            'limit' => 10,
            'order' => 'desc',
            'offchain' => true,
            'owner_guid' => 9012
        ];
        $this->elasticRepository->getList($params)->shouldBeCalled()->willReturn($response);
        $response->toArray()->shouldbeCalled()->willReturn([$boost]);
        $this->cassandraRepository->getList(Argument::any())->shouldBeCalled()->willReturn($response);

        $boost->getGuid()->shouldBeCalled()->willReturn('9012');
        $boost->getEntityGuid()->shouldBeCalled()->willReturn('1234');
        $boost->getOwnerGuid()->shouldBeCalled()->willReturn(5678);

        $response->getPagingToken()->shouldBeCalled()->willReturn(1571060432);
        $response->setPagingToken(1571060432)->shouldBeCalled();

        $response->rewind()->shouldBeCalled();
        $response->valid()->shouldBeCalled()->willReturn(true, false);
        $response->current()->shouldBeCalled()->willReturn($boost);
        $response->key()->shouldBeCalled()->willReturn(0);


        $boost->setEntity(null)->shouldBeCalled(); // TODO: Expand with entitiesBuilder mock call
        $boost->setOwner(null)->shouldBeCalled(); // TODO: Expand with entitiesBuilder mock call
        $boost->getEntity()->shouldBeCalled()->willReturn(null);
        $boost->setEntity(Argument::type(Entity::class))->shouldBeCalled();

        $response->next()->shouldBeCalled();

        $this->getOffchainBoosts('newsfeed', 9012)->shouldHaveType('Minds\Common\Repository\Response');
    }

    public function it_should_recognise_a_user_has_reached_the_offchain_boost_limit(Boost $boost)
    {
        $boostArray = [];
        for ($i = 0; $i < 10; $i++) {
            $newBoost = new Boost();
            $newBoost->setCreatedTimestamp('9999999999999999');
            $newBoost->setImpressions(1000);
            array_push($boostArray, $newBoost);
        }
        Di::_()->get('Config')->set('max_daily_boost_views', 10000);
        $this->runThroughGetList($boost, $boostArray);
        $this->isBoostLimitExceededBy($boost)->shouldReturn(true);
    }

    public function it_should_recognise_a_user_has_NOT_reached_the_offchain_boost_limit(Boost $boost)
    {
        $boostArray = [];
        for ($i = 0; $i < 9; $i++) {
            $newBoost = new Boost();
            $newBoost->setCreatedTimestamp('9999999999999999');
            $newBoost->setImpressions(1000);
            array_push($boostArray, $newBoost);
        }
        Di::_()->get('Config')->set('max_daily_boost_views', 10000);
        $this->runThroughGetList($boost, $boostArray);
        $this->isBoostLimitExceededBy($boost)->shouldReturn(false);
    }


    public function it_should_recognise_a_boost_would_take_user_above_offchain_limit(Boost $boost)
    {
        $boostArray = [];
        for ($i = 0; $i < 2; $i++) {
            $newBoost = new Boost();
            $newBoost->setCreatedTimestamp('9999999999999999');
            $newBoost->setImpressions(4501);
            array_push($boostArray, $newBoost);
        }
        Di::_()->get('Config')->set('max_daily_boost_views', 10000);
        $this->runThroughGetList($boost, $boostArray);
        $this->isBoostLimitExceededBy($boost)->shouldReturn(true);
    }

    public function it_should_allow_a_user_to_boost_onchain_when_offchain_limit_reached(Boost $boost)
    {
        $boostArray = [];
        for ($i = 0; $i < 2; $i++) {
            $newBoost = new Boost();
            $newBoost->setCreatedTimestamp('9999999999999999');
            $newBoost->setImpressions(5001);
            array_push($boostArray, $newBoost);
        }

        $boost->isOnChain()
            ->shouldBeCalled()
            ->willReturn(true);

        Di::_()->get('Config')->set('max_daily_boost_views', 20000);
        $this->isBoostLimitExceededBy($boost)->shouldReturn(false);
    }

    public function runThroughGetList($boost, $existingBoosts, $onchain = false)
    {
        $this->elasticRepository->getList([
            "useElastic" => true,
            "state" => "active",
            "type" => "newsfeed",
            "limit" => 10,
            "order" => "desc",
            "offchain" => true,
            "owner_guid" => "123"
        ])
            ->shouldBeCalled()
            ->willReturn(new Response($existingBoosts, ''));

        $this->cassandraRepository->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn(new Response($existingBoosts));

        $boost->getType()
            ->shouldBeCalled()
            ->willReturn('newsfeed');

        $boost->isOnChain()
            ->shouldBeCalled()
            ->willReturn($onchain);

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $boost->getImpressions()
            ->shouldBeCalled()
            ->willReturn(1000);
    }

    public function it_should_expire_a_boost(Boost $boost)
    {
        $boost->getState()->shouldBeCalled()->willReturn('created');
        $boost->setCompletedTimestamp(Argument::approximate(time() * 1000, -5))->shouldBeCalled()->willReturn($boost);
        $boost->getOwnerGuid()->shouldBeCalled()->willReturn(123);
        $boost->getEntity()->shouldBeCalled()->willReturn((new Entity));
        $boost->getImpressions()->shouldBeCalled();

        $this->expire($boost);
    }
}
