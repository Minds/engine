<?php

namespace Spec\Minds\Core\Supermind\Events;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Security\ACL;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\Events\Events;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventsSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    private $eventsDispatcherMock;

    /** @var SupermindManager */
    private $supermindManagerMock;

    /** @var EntitiesBuilder */
    private $entitiesBuilderMock;

    /** @var ACL */
    private $aclMock;

    public function let(
        EventsDispatcher $eventsDispatcherMock,
        SupermindManager $supermindManagerMock,
        EntitiesBuilder $entitiesBuilderMock,
        ACL $aclMock
    ) {
        Di::_()->bind('EventsDispatcher', function ($di) {
            return new EventsDispatcher();
        });

        $this->beConstructedWith(null, $supermindManagerMock, $entitiesBuilderMock, $aclMock);

        $this->eventsDispatcherMock = $eventsDispatcherMock;
        $this->supermindManagerMock = $supermindManagerMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->aclMock = $aclMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Events::class);
    }

    public function it_should_register_events()
    {
        $this->beConstructedWith($this->eventsDispatcherMock);

        $this->eventsDispatcherMock->register('acl:read', 'supermind', Argument::type('callable'))
            ->shouldBeCalled();

        $this->eventsDispatcherMock->register('acl:write', 'supermind', Argument::type('callable'))
            ->shouldBeCalled();

        $this->eventsDispatcherMock->register('export:extender', 'activity', Argument::type('callable'))
            ->shouldBeCalled();

        $this->register();
    }

    // public function it_should_extend_activity_export_for_supermind_request(SupermindRequest $supermindRequest, User $receiverUser)
    // {
    //     $this->register(); // Setup hooks

    //     $this->aclMock->setIgnore(true)->shouldBeCalled()->willReturn(false);
    //     $this->aclMock->setIgnore(false)->shouldBeCalled();

    //     $this->supermindManagerMock->getRequest('123')
    //         ->willReturn($supermindRequest);

    //     $supermindRequest->getReceiverGuid()->willReturn('456');
    //     $supermindRequest->getReplyActivityGuid()->willReturn('789');

    //     $this->entitiesBuilderMock->single('456')
    //         ->willReturn($receiverUser);

    //     $activity = new Activity();
    //     $activity->setSupermind([
    //         'request_guid' => '123',
    //         'is_reply' => false,
    //     ]);
        
    //     $response = Di::_()->get('EventsDispatcher')->trigger('export:extender', 'activity', ['entity'=>$activity], []);
    // }
}
