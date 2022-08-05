<?php

namespace Spec\Minds\Core\Nostr;

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

    public function let(Manager $manager, Save $saveAction, ACL $acl, Feeds\Activity\Manager $activityManager)
    {
        $this->beConstructedWith($manager, $saveAction, $acl, $activityManager);
        $this->managerMock = $manager;
        $this->saveActionMock = $saveAction;
        $this->aclMock = $acl;
        $this->activityManagerMock = $activityManager;
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

        $this->managerMock->isOnWhitelist('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->willReturn(true);

        $this->managerMock->getUserByPublicKey('36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b')
            ->wilLReturn($owner);

        $this->managerMock->verifyEvent(Argument::any())
            ->willReturn(true);

        $this->managerMock->beginTransaction()->willReturn(true);

        $this->managerMock->addEvent($nostrEvent)
            ->shouldBeCalled();

        $owner->setName('markonnostrtest2')->shouldBeCalled();
        $owner->setBriefDescription(('hello world'))->shouldBeCalled();

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
    "id": "0d5e3045691b2a4002481ef600b3b6600931137e02d151ba17ab425b64f6abde",
    "pubkey": "36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b",
    "created_at": 1658236946,
    "kind": 0,
    "tags": [],
    "content": "{\"name\":\"markonnostrtest2\",\"about\":\"hello world\"}",
    "sig": "07c0f7d55fc678ed8ca90d20f437778d979f794329916c8e8991419af1e0da731b265f16127cd0eaf9e37e0d2b47949cd53dd4fbc97946b5f3d230c7aae0d21c"
}
END;
        $rawNostrEventArray = json_decode($rawNostrEvent, true);
        return NostrEvent::buildFromArray($rawNostrEventArray);
    }
}
