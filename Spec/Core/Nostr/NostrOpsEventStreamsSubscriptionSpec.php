<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Common\Urn;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Resolver;

use Minds\Entities\Activity;
use Minds\Core\Nostr\NostrEvent;

// use Minds\Core\Nostr\Comment;
use Minds\Core\Nostr\NostrOpsEventStreamsSubscription;
use Minds\Core\Nostr\Manager;
use Minds\Core\Nostr\Repository;
use Minds\Core\Nostr\NIP26DelegateToken;
use Minds\Core\Nostr\Keys;

use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;

use Prophecy\Argument;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class NostrOpsEventStreamsSubscriptionSpec extends ObjectBehavior
{
    protected Collaborator $manager;
    protected Collaborator $entitiesResolver;
    protected Collaborator $entitiesBuilder;
    protected Collaborator $keys;

    public function let(
        Manager $manager,
        Resolver $entitiesResolver,
        EntitiesBuilder $entitiesBuilder,
        Keys $keys
    ) {
        $this->manager = $manager;
        $this->entitiesResolver = $entitiesResolver;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->keys = $keys;

        $this->beConstructedWith(
            $manager,
            $entitiesResolver,
            $entitiesBuilder,
            $keys
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NostrOpsEventStreamsSubscription::class);
    }

    public function it_should_ack_when_not_op_event(ActionEvent $event)
    {
        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_ack_if_no_nip26(NostrEvent $nostrEvent)
    {
        // Setup Mocks
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;

        $nostrEvent->getId()->willReturn(1);

        // Retrieve entity details
        $this->entitiesResolver->setOpts(['cache' => false])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn($entity);

        // Check if NIP-26 is enabled, in this case it is not
        $this->manager->getPublicKeyFromUser(Argument::any())->willReturn('publicKey');
        $this->keys->getNip26DelegationToken(Argument::any())->willReturn(null);

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_add_nostr_event(NIP26DelegateToken $token, NostrEvent $nostrEvent)
    {
        // Setup Mocks
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_CREATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;

        $nostrEvent->getId()->willReturn(1);

        // Retrieve entity details
        $this->entitiesResolver->setOpts(['cache' => false])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn($entity);

        // Check if NIP-26 is enabled, in this case it is
        $this->manager->getPublicKeyFromUser(Argument::any())->willReturn('publicKey');
        $this->keys->getNip26DelegationToken(Argument::any())->willReturn($token);

        // Get Nostr Event from activity id
        $this->manager->getNostrEventFromActivityId(Argument::any())->willReturn(null);

        // Add Nostr Event
        $this->manager->buildNostrEvent(Argument::any())->willReturn($nostrEvent);
        $this->manager->addEvent(Argument::any())->shouldBeCalled()->willReturn(true);
        $this->manager->addActivityToNostrId(Argument::any(), Argument::any())->shouldBeCalled()->willReturn(true);

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_update_nostr_event(NIP26DelegateToken $token, NostrEvent $nostrEvent)
    {
        // Setup Mocks
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_UPDATE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;

        $nostrEvent->getId()->willReturn(1);

        // Retrieve entity details
        $this->entitiesResolver->setOpts(['cache' => false])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn($entity);

        // Check if NIP-26 is enabled, in this case it is
        $this->manager->getPublicKeyFromUser(Argument::any())->willReturn('publicKey');
        $this->keys->getNip26DelegationToken(Argument::any())->willReturn($token);

        // Get Nostr Event from activity id
        $this->manager->getNostrEventFromActivityId(Argument::any())->willReturn(null);

        // Update Nostr Event
        $this->manager->buildNostrEvent(Argument::any())->willReturn($nostrEvent);
        $this->manager->addEvent(Argument::any())->shouldBeCalled()->willReturn(true);
        $this->manager->addActivityToNostrId(Argument::any(), Argument::any())->shouldBeCalled()->willReturn(true);
        $this->manager->deleteNostrEvents(Argument::any())->shouldBeCalled()->willReturn(true);

        $this->consume($event)->shouldReturn(true);
    }

    public function it_should_delete_nostr_event(NIP26DelegateToken $token, NostrEvent $nostrEvent)
    {
        // Setup Mocks
        $entityUrn = 'urn:activity:123';
        $ownerGuid = '234';

        $event = new EntitiesOpsEvent();
        $event->setOp(EntitiesOpsEvent::OP_DELETE)
            ->setEntityUrn($entityUrn);

        $entity = new Activity();
        $entity->owner_guid = $ownerGuid;

        $nostrEvent->getId()->willReturn(1);

        // Retrieve entity details
        $this->entitiesResolver->setOpts(['cache' => false])
            ->shouldBeCalled()
            ->willReturn($this->entitiesResolver);

        $this->entitiesResolver->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn($entity);

        // Check if NIP-26 is enabled, in this case it is
        $this->manager->getPublicKeyFromUser(Argument::any())->willReturn('publicKey');
        $this->keys->getNip26DelegationToken(Argument::any())->willReturn($token);

        // Get Nostr Event from activity id
        $this->manager->getNostrEventFromActivityId(Argument::any())->willReturn(null);

        // Delete Nostr Event
        $this->manager->deleteNostrEvents(Argument::any())->shouldBeCalled()->willReturn(true);

        $this->consume($event)->shouldReturn(true);
    }
}
