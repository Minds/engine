<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Common\Urn;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
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
    private $repository;
    private $keys;

    public function let(EntitiesBuilder $entitiesBuilder, Keys $keys, Repository $repository, EntitiesResolver $entitiesResolver)
    {
        $this->beConstructedWith(null, $entitiesBuilder, $keys, [], $repository, $entitiesResolver);
        $this->entitiesBuilder = $entitiesBuilder;
        $this->repository = $repository;
        $this->entitiesResolver = $entitiesResolver;
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

        $this->getPublicKeyFromUsername('phpspec')
            ->shouldBe('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');
    }

    public function it_should_return_public_key_from_a_user(User $user)
    {
        $this->keys->withUser($user)
            ->willReturn($this->keys);
        $this->keys->getSecp256k1PublicKey()
            ->willReturn('4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715');

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

    public function it_should_emit_a_nostr_event(\WebSocket\Client $wsClient)
    {
        $this->beConstructedWith(null, $this->entitiesBuilder, $this->keys, [$wsClient]);

        $nostrEvent = new NostrEvent();
        $nostrEvent->setId("c7462cd60b3278e59cf863a512971b2c35da77aabd6761eb76d1e42083da9038")
            ->setKind(1)
            ->setCreated_at(1653047334)
            ->setPubKey("4b716d963e51cae83e59748197829f1842d3d0a04e916258b26d53bf852b8715")
            ->setSig("9aafd37d5312426c34c4f16d9d837167260c1000b6cb7d111b9a0966692ee04a4c93af15767c521eab9b660ee4169b489f8023f836403388f970ad52bbbaf995")
            ->setContent('Hello nostr. This is Minds calling');

        $wsClient->text(Argument::any())
            ->shouldBeCalled();

        $this->emitEvent($nostrEvent);
    }

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

    /**
     * TODO: Fix spec tests once code is merged.
     * @param Activity $activityMock
     * @param User $userMock
     * @param Urn $entityUrn
     * @return void
     * @throws ServerErrorException
     */
//    public function it_should_add_nostr_hash_link_to_entity(
//        Activity $activityMock,
//        User $userMock,
//        Urn $entityUrn
//    ): void {
//        /**
//         * Set up entity urn mock
//         */
//        $entityUrn->getUrn()
//            ->willReturn("entity_urn");
//
//        /**
//         * Set up entity mock
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
//         * Set up entities builder mock
//         */
//        $this->entitiesBuilder->single("user_123")
//            ->willReturn($userMock);
//
//        /**
//         * Set up entity resolver mock
//         */
//        $this->entitiesResolver->setOpts(Argument::type('array'))
//            ->willReturn($this->entitiesResolver);
//
//        $this->entitiesResolver->single($entityUrn)
//            ->shouldBeCalledOnce()
//            ->willReturn($activityMock);
//
//        /**
//         * Set up repository mock
//         */
//        $this->repository->addNewCorrelation(Argument::type("string"), Argument::type("string"), Argument::type("string"))
//            ->shouldBeCalledOnce()
//            ->willReturn(true);
//
//        /**
//         * Set up Nostr keys mock
//         */
//        $this->setupKeysMock($userMock);
//
//        $this->buildNostrEvent($activityMock)
//            ->willReturn(new NostrEvent());
//
//        $this->addNostrHashLinkToEntity($entityUrn);
//    }

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
