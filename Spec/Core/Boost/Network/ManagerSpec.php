<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Boost\Network\ElasticRepository;
use Minds\Core\Boost\Network\Manager;
use Minds\Core\Boost\Network\Repository;
use Minds\Core\EntitiesBuilder;
use Minds\Core\GuidBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Di\Di;

class ManagerSpec extends ObjectBehavior
{
    private $repository;
    private $elasticRepository;
    private $entitiesBuilder;
    private $guidBuilder;

    public function let(
        Repository $repository,
        ElasticRepository $elasticRepository,
        EntitiesBuilder $entitiesBuilder,
        GuidBuilder $guidBuilder
    ) {
        $this->beConstructedWith($repository, $elasticRepository, $entitiesBuilder, $guidBuilder);
        $this->repository = $repository;
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
        $response = new Response([
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
            'state' => 'review',
            'hydrate' => true,
            'useElastic' => true,
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->repository->getList([
            'state' => 'review',
            'hydrate' => true,
            'useElastic' => true,
            'guids' => [1, 2],
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn((new Activity)
                ->set('guid', 123));

        $this->entitiesBuilder->single(1)
            ->shouldBeCalled()
            ->willReturn((new User)
                ->set('guid', 1));

        $this->entitiesBuilder->single(456)
            ->shouldBeCalled()
            ->willReturn((new Activity)
                ->set('guid', 456));

        $this->entitiesBuilder->single(2)
            ->shouldBeCalled()
            ->willReturn((new User)
                ->set('guid', 2));

        $response = $this->getList([
            'state' => 'review',
        ]);

        $response[0]->getEntity()->getGuid()
            ->shouldBe(123);
        $response[0]->getOwner()->getGuid()
            ->shouldBe(1);
        $response[0]->getImpressions()
            ->shouldBe(1000);

        $response[1]->getEntity()->getGuid()
            ->shouldBe(456);
        $response[1]->getOwner()->getGuid()
            ->shouldBe(2);
        $response[1]->getImpressions()
            ->shouldBe(100);
    }

    public function it_should_return_a_list_of_boosts_to_deliver()
    {
        $this->elasticRepository->getList([
            'state' => 'approved',
            'hydrate' => true,
            'useElastic' => true,
        ])
            ->shouldBeCalled()
            ->willReturn([
                (new Boost)
                    ->setEntityGuid(123)
                    ->setImpressions(1000)
                    ->setOwnerGuid(1),
                (new Boost)
                    ->setEntityGuid(456)
                    ->setImpressions(100)
                    ->setOwnerGuid(2)
            ]);

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn((new Activity)
                ->set('guid', 123));

        $this->entitiesBuilder->single(1)
            ->shouldBeCalled()
            ->willReturn((new User)
                ->set('guid', 1));

        $this->entitiesBuilder->single(456)
            ->shouldBeCalled()
            ->willReturn((new Activity)
                ->set('guid', 456));

        $this->entitiesBuilder->single(2)
            ->shouldBeCalled()
            ->willReturn((new User)
                ->set('guid', 2));

        $response = $this->getList([
            'state' => 'approved',
            'useElastic' => true,
        ]);

        $response[0]->getEntity()->getGuid()
            ->shouldBe(123);
        $response[0]->getOwner()->getGuid()
            ->shouldBe(1);
        $response[0]->getImpressions()
            ->shouldBe(1000);

        $response[1]->getEntity()->getGuid()
            ->shouldBe(456);
        $response[1]->getOwner()->getGuid()
            ->shouldBe(2);
        $response[1]->getImpressions()
            ->shouldBe(100);
    }

    public function it_should_return_a_list_of_boosts_from_guids()
    {
        $this->repository->getList([
            'state' => null,
            'guids' => [123, 456],
            'hydrate' => true,
            'useElastic' => false,
        ])
            ->shouldBeCalled()
            ->willReturn([
                (new Boost)
                    ->setEntityGuid(123)
                    ->setImpressions(1000)
                    ->setOwnerGuid(1),
                (new Boost)
                    ->setEntityGuid(456)
                    ->setImpressions(100)
                    ->setOwnerGuid(2)
            ]);

        $this->entitiesBuilder->single(123)
            ->shouldBeCalled()
            ->willReturn((new Activity)
                ->set('guid', 123));

        $this->entitiesBuilder->single(1)
            ->shouldBeCalled()
            ->willReturn((new User)
                ->set('guid', 1));

        $this->entitiesBuilder->single(456)
            ->shouldBeCalled()
            ->willReturn((new Activity)
                ->set('guid', 456));

        $this->entitiesBuilder->single(2)
            ->shouldBeCalled()
            ->willReturn((new User)
                ->set('guid', 2));

        $response = $this->getList([
            'guids' => [123, 456],
        ]);

        $response[0]->getEntity()->getGuid()
            ->shouldBe(123);
        $response[0]->getOwner()->getGuid()
            ->shouldBe(1);
        $response[0]->getImpressions()
            ->shouldBe(1000);

        $response[1]->getEntity()->getGuid()
            ->shouldBe(456);
        $response[1]->getOwner()->getGuid()
            ->shouldBe(2);
        $response[1]->getImpressions()
            ->shouldBe(100);
    }

    public function it_should_add_a_boost(Boost $boost)
    {
        $this->guidBuilder->build()
            ->shouldBeCalled()
            ->willReturn(1);

        $boost->getGuid()
            ->shouldbeCalled()
            ->willReturn(null);

        $boost->setGuid(1)
            ->shouldBeCalled();

        $this->repository->add($boost)
            ->shouldBeCalled();
        $this->elasticRepository->add($boost)
            ->shouldBeCalled();

        $this->add($boost)
            ->shouldReturn(true);
    }

    public function it_should_update_a_boost(Boost $boost)
    {
        $this->repository->update($boost, ['@timestamp'])
            ->shouldBeCalled();
        $this->elasticRepository->update($boost, ['@timestamp'])
            ->shouldBeCalled();

        $this->update($boost, ['@timestamp']);
    }

    public function it_should_resync_a_boost_on_elasticsearch(Boost $boost)
    {
        $this->elasticRepository->update($boost, ['@timestamp'])
            ->shouldBeCalled();

        $this->resync($boost, ['@timestamp']);
    }

    public function it_should_check_if_the_entity_was_already_boosted(Boost $boost)
    {
        $this->elasticRepository->getList([
            'hydrate' => true,
            'useElastic' => true,
            'state' => 'review',
            'type' => 'newsfeed',
            'entity_guid' => '123',
            'limit' => 1
        ])
            ->shouldBeCalled()
            ->willReturn(new Response([$boost], ''));

        $this->repository->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn(new Response([$boost]));

        $boost->getType()
            ->shouldBeCalled()
            ->willReturn('newsfeed');

        $boost->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->checkExisting($boost)->shouldReturn(true);
    }

    public function it_should_request_offchain_boosts(Boost $boost)
    {
        $this->elasticRepository->getList([
            "hydrate" => true,
            "useElastic" => true,
            "state" => "active",
            "type" => "newsfeed",
            "limit" => 10,
            "order" => "desc",
            "offchain" => true,
            "owner_guid" => "123"
        ])
            ->shouldBeCalled()
            ->willReturn(new Response([$boost], ''));

        $this->repository->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn(new Response([$boost]));

        $boost->getType()
            ->shouldBeCalled()
            ->willReturn('newsfeed');

        $boost->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->getOffchainBoosts($boost)->shouldHaveType('Minds\Common\Repository\Response');
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

    public function it_should_allow_a_pro_user_to_boost_when_offchain_limit_reached(Boost $boost, User $user)
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
            ->willReturn(false);

        $boost->getOwner()
            ->shouldBeCalled()
            ->willReturn($user);

        $user->isAdmin()
            ->willReturn(false);

        $user->isPro()
            ->willReturn(true);

        Di::_()->get('Config')->set('max_daily_boost_views', 20000);
        $this->isBoostLimitExceededBy($boost)->shouldReturn(false);
    }

    public function it_should_allow_an_admin_to_boost_when_offchain_limit_reached(Boost $boost, User $user)
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
            ->willReturn(false);

        $boost->getOwner()
            ->shouldBeCalled()
            ->willReturn($user);

        $user->isAdmin()
            ->willReturn(true);

        Di::_()->get('Config')->set('max_daily_boost_views', 20000);
        $this->isBoostLimitExceededBy($boost)->shouldReturn(false);
    }

    public function runThroughGetList($boost, $existingBoosts, $onchain = false)
    {
        $this->elasticRepository->getList([
            "hydrate" => true,
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
        
        $this->repository->getList(Argument::any())
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

        $boost->getOwner()
            ->shouldBeCalled();
    }
}
