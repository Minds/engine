<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Core\Channels\AvatarService;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Nostr\EntityImporter;
use Minds\Core\Nostr\NostrEvent;
use Minds\Core\Nostr\Manager;
use Minds\Core\Security\ACL;
use Minds\Core\Feeds;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PDOException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EntityImporterSpec extends ObjectBehavior
{
    protected $managerMock;
    protected $saveActionMock;
    protected $aclMock;
    protected $activityManagerMock;
    protected $avatarServiceMock;

    public function let(Manager $manager, Save $saveAction, ACL $acl, Feeds\Activity\Manager $activityManager, AvatarService $avatarServiceMock)
    {
        $this->beConstructedWith($manager, $saveAction, $acl, $activityManager, $avatarServiceMock);
        $this->managerMock = $manager;
        $this->saveActionMock = $saveAction;
        $this->aclMock = $acl;
        $this->activityManagerMock = $activityManager;
        $this->avatarServiceMock = $avatarServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EntityImporter::class);
    }

    //
    // MH: hard to mock 'register_user' function...
    //
    // public function it_should_create_user()
    // {
    // }

    public function it_should_throw_if_not_on_whitelist()
    {
        $nostrEvent = $this->getNostrEventKind1Mock();

        $this->managerMock->isOnWhitelist('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->willReturn(false);

        $this->shouldThrow(UserErrorException::class)->duringOnNostrEvent($nostrEvent);
    }

    public function it_should_throw_if_signature_fails(User $owner)
    {
        $nostrEvent = $this->getNostrEventKind1Mock();

        $this->managerMock->isOnWhitelist('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->willReturn(true);

        $this->managerMock->getUserByPublicKey('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->wilLReturn($owner);

        $this->managerMock->verifyEvent(Argument::any())
            ->willReturn(false);

        $this->shouldThrow(UserErrorException::class)->duringOnNostrEvent($nostrEvent);
    }

    public function it_should_rollback_on_error(User $owner)
    {
        $nostrEvent = $this->getNostrEventKind1Mock();

        $this->managerMock->isOnWhitelist('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->willReturn(true);

        $this->managerMock->getUserByPublicKey('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->wilLReturn($owner);

        $this->managerMock->verifyEvent(Argument::any())
            ->willReturn(true);

        $this->managerMock->beginTransaction()->willReturn(true);

        // Throw exec
        $this->managerMock->addEvent($nostrEvent)->willThrow(new PDOException());

        $this->managerMock->rollBack()
            ->willReturn(true);
        $this->managerMock->rollBack()
            ->shouldBeCalled();

        // We rethrow the execption after rollback
        $this->shouldThrow(PDOException::class)->duringOnNostrEvent($nostrEvent);
    }

    public function it_should_import_activity_post(User $owner)
    {
        $nostrEvent = $this->getNostrEventKind1Mock();

        $this->managerMock->isOnWhitelist('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->willReturn(true);

        $this->managerMock->getUserByPublicKey('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->wilLReturn($owner);

        $this->managerMock->verifyEvent(Argument::any())
            ->willReturn(true);

        $this->managerMock->beginTransaction()->willReturn(true);

        $this->managerMock->addMention(
            "af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c",
            Argument::any()
        )->willReturn(true);

        $this->managerMock->addReply(
            "af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c",
            Argument::any()
        )->willReturn(true);

        $this->managerMock->addEvent($nostrEvent)
            ->shouldBeCalled();

        $this->managerMock->getActivityFromNostrId('af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c')
            ->willReturn(null);

        $this->activityManagerMock->add(Argument::any());

        $this->managerMock->addActivityToNostrId(Argument::that(function ($activity) {
            return $activity->getMessage() === 'Hello sandbox'
                && $activity->getSource() === 'nostr';
        }), 'af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c')
            ->shouldBeCalled();

        $this->managerMock->commit()->willReturn(true);

        $this->onNostrEvent($nostrEvent);
    }

    public function it_should_import_channel_metadata(User $owner)
    {
        $nostrEvent = $this->getNostrEventKind0Mock();

        $this->managerMock->isOnWhitelist('86f0689bd48dcd19c67a19d994f938ee34f251d8c39976290955ff585f2db42e')
            ->willReturn(true);

        $this->managerMock->getUserByPublicKey('86f0689bd48dcd19c67a19d994f938ee34f251d8c39976290955ff585f2db42e')
            ->wilLReturn($owner);

        $this->managerMock->verifyEvent(Argument::any())
            ->willReturn(true);

        $this->managerMock->beginTransaction()->willReturn(true);

        $this->managerMock->addEvent($nostrEvent)
            ->shouldBeCalled();

        $owner->setName('markharding_test1')->shouldBeCalled();
        $owner->setBriefDescription(('hello world'))->shouldBeCalled();

        $this->avatarServiceMock->withUser($owner)
            ->willReturn($this->avatarServiceMock);

        $this->avatarServiceMock->createFromUrl('https://cdn.minds.com/icon/100000000000000063/master/1654594990')
            ->shouldBeCalled();

        $this->saveActionMock->setEntity($owner)
            ->willReturn($this->saveActionMock);
        $this->saveActionMock->save()
            ->shouldBeCalled();

        $this->managerMock->commit()->willReturn(true);

        $this->onNostrEvent($nostrEvent);
    }

    protected function getNostrEventKind1Mock(): NostrEvent
    {
        $rawNostrEvent = <<<END
{
    "id": "af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c",
    "pubkey": "36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b",
    "created_at": 1658238691,
    "kind": 1,
    "tags": [["p", "c59bb3bb07b087ef9fbd82c9530cf7de9d28adfdeb5076a0ac39fa44b88a49ad"],["e", "50eaadde6fd5a67b9a35f947355e3f90d6043d888008c4dbdb36c06155cf31ea"]],
    "content": "Hello sandbox",
    "sig": "f6a68256a42f9fd84948e328300d0ca55160c9517cd57e549381ce9106e89946ee58c468b93e7ed419f2aec4844c1e995987d27119f9988e99ea2da8dfafffec"
}
END;

        $rawNostrEventArray = json_decode($rawNostrEvent, true);
        return NostrEvent::buildFromArray($rawNostrEventArray);
    }

    protected function getNostrEventKind0Mock(): NostrEvent
    {
        $rawNostrEvent = <<<END
{
    "pubkey":"86f0689bd48dcd19c67a19d994f938ee34f251d8c39976290955ff585f2db42e",
    "created_at":1660312711,
    "kind":0,
    "tags":[],
    "content":"{\"name\":\"markharding_test1\",\"picture\":\"https://cdn.minds.com/icon/100000000000000063/master/1654594990\",\"about\":\"hello world\"}",
    "id":"55f920dcec572e37cc1cb1ef2cf10e7ec07bb7e4be9fd89be7a223774469870e",
    "sig":"9ba42699f4bf87503ae8a84b0c3699f3419a2bc0e2b960582b36a915000c6fdefd5632c034c10392ce537f5062cf5fc4ee6bdf4070843e78476693e491328aed"
}
END;
        $rawNostrEventArray = json_decode($rawNostrEvent, true);
        return NostrEvent::buildFromArray($rawNostrEventArray);
    }
}
