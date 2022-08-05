<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Common\Urn;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Core\Nostr\Keys;
use Minds\Core\Nostr\Manager;
use Minds\Core\Nostr\NostrEvent;
use Minds\Core\Nostr\Repository;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $entitiesBuilder;
    private $entitiesResolver;
    private $elasticSearchManager;
    private $repository;
    private $keys;

    public function let(
        EntitiesBuilder $entitiesBuilder,
        Keys $keys,
        Repository $repository,
        EntitiesResolver $entitiesResolver,
        ElasticSearchManager $elasticSearchManager
    ) {
        $this->beConstructedWith(
            null,
            $entitiesBuilder,
            $keys,
            $repository,
            $entitiesResolver,
            $elasticSearchManager
        );
        $this->entitiesBuilder = $entitiesBuilder;
        $this->repository = $repository;
        $this->entitiesResolver = $entitiesResolver;
        $this->elasticSearchManager = $elasticSearchManager;
        $this->keys = $keys;
    }

    public function getMatchers(): array
    {
        return [
            'containValueLike' => function ($subject, $value) {
                foreach ($subject as $item) {
                    print_r($item);
                    if ($item == $value) {
                        return true;
                    }
                }
                return false;
            }
        ];
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_public_key_from_a_username(User $user)
    {
        $this->entitiesBuilder->getByUserByIndex('phpspec')
            ->willReturn($user);

        $this->keys->withUser($user)
            ->willReturn($this->keys);
        $this->keys->getSecp256k1PublicKey()
            ->willReturn('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');

        //

        $user->getUrn()
            ->willReturn('urn:user:123');

        $this->repository->addNostrUser($user, '4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715')
            ->shouldBeCalled();

        //

        $this->getPublicKeyFromUsername('phpspec')
            ->shouldBe('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');
    }

    public function it_should_return_public_key_from_a_user(User $user)
    {
        $this->keys->withUser($user)
            ->willReturn($this->keys);
        $this->keys->getSecp256k1PublicKey()
            ->willReturn('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');

        //

        $user->getUrn()
            ->willReturn('urn:user:123');

        $this->repository->addNostrUser($user, '4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715')
            ->shouldBeCalled();

        //

        $this->getPublicKeyFromUser($user)
            ->shouldBe('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');
    }

    public function it_should_build_a_nostr_event_for_user()
    {
        $user = new User();
        $user->username = 'phpspec';
        $user->briefdescription = 'dont feel like saying much';
        $user->time_created = 1653047334;
        $user->icontime = 1653047334;

        $this->keys->withUser($user)
            ->willReturn($this->keys);
        $this->keys->getSecp256k1PublicKey()
            ->willReturn('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');
        $this->keys->getSecp256k1PrivateKey()
            ->willReturn(pack('H*', "51931a1fffbb7e408099d615b283c5a8615a23695b0e46e943e74f404c95042a"));

        $nostrEvent = $this->buildNostrEvent($user);
        $nostrEvent->getId()->shouldBe('9a6632c7bd77040c167241bc9796836914532bc669e7f56170d37a7c91f4a1a2');
        $nostrEvent->getKind()->shouldBe(0);
        $nostrEvent->getPubKey()->shouldBe("4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715");
        $nostrEvent->getSig()->shouldBe("4711a52137e39ab65e9e5cd0bc9932d50b03bc239cf7bd810bee6ba42355a6f795c8ea247f2515f323c65db18787b609bfe42406ade659548425b1dea21761b0");
    }

    public function it_should_build_a_nostr_event_for_activity(Activity $activity, User $user)
    {
        $activity->getOwnerGuid()
            ->willReturn("123");
        $activity->getTimeCreated()
            ->willReturn(1653047334);
        $activity->getMessage()
            ->willReturn('Hello nostr. This is Minds calling');
        $activity->getEntityGuid()
            ->willReturn(null);
        $activity->isRemind()
            ->willReturn(false);
        $activity->isQuotedPost()
            ->willReturn(false);

        $this->entitiesBuilder->single("123")
            ->willReturn($user);

        $this->keys->withUser($user)
            ->willReturn($this->keys);
        $this->keys->getSecp256k1PublicKey()
            ->willReturn('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');
        $this->keys->getSecp256k1PrivateKey()
            ->willReturn(pack('H*', "51931a1fffbb7e408099d615b283c5a8615a23695b0e46e943e74f404c95042a"));

        $nostrEvent = $this->buildNostrEvent($activity);
        $nostrEvent->getId()->shouldBe('c7462cd60b3278e59cf863a512971b2c35da77aabd6761eb76d1e42083da9038');
        $nostrEvent->getKind()->shouldBe(1);
        $nostrEvent->getPubKey()->shouldBe("4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715");
        $nostrEvent->getSig()->shouldBe("9aafd37d5312426c34c4f16d9d837167260c1000b6cb7d111b9a0966692ee04a4c93af15767c521eab9b660ee4169b489f8023f836403388f970ad52bbbaf995");
        $nostrEvent->getContent()->shouldBe('Hello nostr. This is Minds calling');
    }

    // public function it_should_emit_a_nostr_event(\WebSocket\Client $wsClient)
    // {
    //     $this->beConstructedWith(null, $this->entitiesBuilder, $this->keys);

    //     $nostrEvent = new NostrEvent();
    //     $nostrEvent->setId("c7462cd60b3278e59cf863a512971b2c35da77aabd6761eb76d1e42083da9038")
    //         ->setKind(1)
    //         ->setCreated_at(1653047334)
    //         ->setPubKey("4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715")
    //         ->setSig("9aafd37d5312426c34c4f16d9d837167260c1000b6cb7d111b9a0966692ee04a4c93af15767c521eab9b660ee4169b489f8023f836403388f970ad52bbbaf995")
    //         ->setContent('Hello nostr. This is Minds calling');

    //     $wsClient->text(Argument::any())
    //         ->shouldBeCalled();

    //     $this->emitEvent($nostrEvent);
    // }

    /**
     * TODO: fix spec tests after code has been merged.
     * @param Activity $activityMock
     * @param User $userMock
     * @return void
     * @throws NotFoundException
     * @throws ServerErrorException
     */
//    public function it_should_return_nostr_events_from_nostr_authors(
//        Activity $activityMock,
//        User $userMock
//    ): void {
//        /**
//         * Set up mock entity
//         */
//        $activityMock->getOwnerGuid()
//            ->willReturn("user_123");
//        $activityMock->getTimeCreated()
//            ->willReturn(1653047334);
//        $activityMock->getMessage()
//            ->willReturn('Hello nostr. This is Minds calling');
//        $activityMock->getEntityGuid()
//            ->willReturn(null);
//        $activityMock->isRemind()
//            ->willReturn(false);
//        $activityMock->isQuotedPost()
//            ->willReturn(false);
//        $activityMock->getType()
//            ->willReturn('activity');
//
//        /**
//         * Set up repository mock
//         */
//        $this->repository->getEntitiesByNostrAuthors(["123"])
//            ->willYield([$activityMock->getWrappedObject()]);
//
//        $this->setupKeysMock($userMock);
//
//        $this->entitiesBuilder->single("user_123")
//            ->willReturn($userMock);
//
//        $nostrEventMock = (new NostrEvent())
//            ->setId("c7462cd60b3278e59cf863a512971b2c35da77aabd6761eb76d1e42083da9038")
//            ->setKind(1)
//            ->setCreated_at(1653047334)
//            ->setPubKey("4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715")
//            ->setSig("9aafd37d5312426c34c4f16d9d837167260c1000b6cb7d111b9a0966692ee04a4c93af15767c521eab9b660ee4169b489f8023f836403388f970ad52bbbaf995")
//            ->setContent('Hello nostr. This is Minds calling');
//
//        $this->buildNostrEvent($activityMock->getWrappedObject())
//            ->willReturn($nostrEventMock);
//
//        $response = $this->getNostrEventsForAuthors(["123"]);
//        $response->shouldContainValueLike($nostrEventMock);
//    }

    public function it_should_accept_default_filters(NostrEvent $nostrEvent, Activity $activity, User $user)
    {
        $limit = 12;
        $filters = [
            'ids' => [],
            'authors' => ['4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715'],
            'kinds' => [ 0, 1 ],
            'since' => null,
            'until' => null,
            'limit' => 12
        ];

        $this->repository->getUserFromNostrPublicKeys(Argument::any())
            ->willReturn([]);

        $this->elasticSearchManager->getList(Argument::any())->willReturn([$activity]);

        $this->getElasticNostrEvents($filters, $limit)->shouldReturn([]);
    }

    public function it_should_get_internal_pubkeys_if_kind_0_lacks_authors(Activity $activity)
    {
        $limit = 12;
        $filters = [
            'ids' => [],
            'authors' => [],
            'kinds' => [ 0 ],
            'since' => null,
            'until' => null,
            'limit' => 12
        ];

        $this->repository->getInternalPublicKeys($limit)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->elasticSearchManager->getList(Argument::any())->willReturn([$activity]);

        $this->getElasticNostrEvents($filters, $limit)->shouldReturn([]);
    }

    public function it_should_set_opts_for_since_filter(Activity $activity)
    {
        $limit = 12;
        $filters = [
            'ids' => [],
            'authors' => [],
            'kinds' => [ 1 ],
            'since' => null,
            'until' => null,
            'limit' => 12,
            'since' => 123
        ];

        $opts = [
            'container_guid' => [],
            'period' => 'all',
            'algorithm' => 'latest',
            'type' => 'activity',
            'limit' => 12,
            'single_owner_threshold' => 0,
            'access_id' => 2,
            'as_activities' => true,
            'from_timestamp' => 123000,
            'reverse_sort' => true
        ];

        $this->elasticSearchManager->getList($opts)->shouldBeCalled()->willReturn([$activity]);

        $this->getElasticNostrEvents($filters, $limit)->shouldReturn([]);
    }

    public function it_should_set_opts_for_until_filter(Activity $activity)
    {
        $limit = 12;
        $filters = [
            'ids' => [],
            'authors' => [],
            'kinds' => [ 1 ],
            'since' => null,
            'until' => null,
            'limit' => 12,
            'until' => 123
        ];

        $opts = [
            'container_guid' => [],
            'period' => 'all',
            'algorithm' => 'latest',
            'type' => 'activity',
            'limit' => 12,
            'single_owner_threshold' => 0,
            'access_id' => 2,
            'as_activities' => true,
            'from_timestamp' => 123000
        ];

        $this->elasticSearchManager->getList($opts)->shouldBeCalled()->willReturn([$activity]);

        $this->getElasticNostrEvents($filters, $limit)->shouldReturn([]);
    }

    public function it_should_set_opts_for_since_and_until_filters(Activity $activity)
    {
        $limit = 12;
        $filters = [
            'ids' => [],
            'authors' => [],
            'kinds' => [ 1 ],
            'since' => null,
            'until' => null,
            'limit' => 12,
            'since' => 123,
            'until' => 456
        ];

        $opts = [
            'container_guid' => [],
            'period' => 'all',
            'algorithm' => 'latest',
            'type' => 'activity',
            'limit' => 12,
            'single_owner_threshold' => 0,
            'access_id' => 2,
            'as_activities' => true,
            'to_timestamp' => 123000,
            'from_timestamp' => 456000
        ];

        $this->elasticSearchManager->getList($opts)->shouldBeCalled()->willReturn([$activity]);

        $this->getElasticNostrEvents($filters, $limit)->shouldReturn([]);
    }

    public function it_should_add_event(NostrEvent $nostrEvent)
    {
        $this->repository->addEvent($nostrEvent)
            ->willReturn(true);

        $this->addEvent($nostrEvent)
            ->shouldBe(true);
    }

    public function it_should_add_reply()
    {
        $this->repository->addReply(
            "8933788dafe23ed6ac5a0d20011fde4769e2096972bb777d728ca62c43fa04d0",
            ["e", "50eaadde6fd5a67b9a35f947355e3f90d6043d888008c4dbdb36c06155cf31ea"]
        )->willReturn(true);

        $this->addReply(
            "8933788dafe23ed6ac5a0d20011fde4769e2096972bb777d728ca62c43fa04d0",
            ["e", "50eaadde6fd5a67b9a35f947355e3f90d6043d888008c4dbdb36c06155cf31ea"]
        )->shouldBe(true);
    }

    public function it_should_add_mention()
    {
        $this->repository->addMention(
            "8933788dafe23ed6ac5a0d20011fde4769e2096972bb777d728ca62c43fa04d0",
            ["p", "c59bb3bb07b087ef9fbd82c9530cf7de9d28adfdeb5076a0ac39fa44b88a49ad"]
        )->willReturn(true);

        $this->addMention(
            "8933788dafe23ed6ac5a0d20011fde4769e2096972bb777d728ca62c43fa04d0",
            ["p", "c59bb3bb07b087ef9fbd82c9530cf7de9d28adfdeb5076a0ac39fa44b88a49ad"]
        )->shouldBe(true);
    }

    public function it_should_add_nostr_user_link(User $user)
    {
        $this->repository->addNostrUser($user, '4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715')
            ->willReturn(true);

        $this->addNostrUser($user, '4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715')
            ->shouldBe(true);
    }

    public function it_should_fetch_activity_from_id()
    {
        $activity = new Activity();
        $activity->guid = '123';

        $this->repository->getActivityFromNostrId('c7462cd60b3278e59cf863a512971b2c35da77aabd6761eb76d1e42083da9038')
            ->willReturn($activity);

        $this->getActivityFromNostrId('c7462cd60b3278e59cf863a512971b2c35da77aabd6761eb76d1e42083da9038')
            ->getGuid()
                ->shouldBe('123');
    }

    public function it_should_add_activity_nostr_id_link(Activity $activity)
    {
        $this->repository->addActivityToNostrId($activity, 'c7462cd60b3278e59cf863a512971b2c35da77aabd6761eb76d1e42083da9038')
            ->willReturn(true);

        $this->addActivityToNostrId($activity, 'c7462cd60b3278e59cf863a512971b2c35da77aabd6761eb76d1e42083da9038')
            ->shouldBe(true);
    }

    public function it_should_query_nostr_events()
    {
        $filters = [];
        $nostrEvent = new NostrEvent();

        $this->repository->getEvents($filters)
            ->willReturn([
                $nostrEvent
            ]);

        $this->getNostrEvents($filters)
            ->shouldYieldLike(new \ArrayIterator([
                $nostrEvent
            ]));
    }

    /**
     * @param User $userMock
     * @return void
     * @throws ServerErrorException
     */
    private function setupKeysMock(User $userMock): void
    {
        /**
         * Set up Nostr keys class
         */
        $this->keys->withUser($userMock)
            ->willReturn($this->keys);
        $this->keys->getSecp256k1PublicKey()
            ->willReturn('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');
        $this->keys->getSecp256k1PrivateKey()
            ->willReturn(pack('H*', "51931a1fffbb7e408099d615b283c5a8615a23695b0e46e943e74f404c95042a"));
    }
}
