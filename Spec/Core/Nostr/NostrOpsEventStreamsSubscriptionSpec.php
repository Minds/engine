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
use Minds\Core\Nostr\Keys;

use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;

use Prophecy\Argument;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class NostrOpsEventStreamsSubscriptionSpec extends ObjectBehavior
{
    private Collaborator $manager;
    private Collaborator $entitiesResolver;
    private Collaborator $entitiesBuilder;
    private Collaborator $keys;

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
}
