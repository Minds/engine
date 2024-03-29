<?php

namespace Spec\Minds\Core\Groups;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Groups\AdminQueue;
use Minds\Entities;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks;
use Minds\Core\Groups\Delegates\PropagateRejectionDelegate;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Notifications\Manager as NotificationsManager;
use PhpSpec\Exception\Example\FailureException;

class FeedsSpec extends ObjectBehavior
{
    protected $_propagateRejectionDelegate;
    protected $adminQueueMock;
    protected $_entities;
    protected $_entitiesFactory;
    protected $_entitiesBuilder;
    protected $_actionEventsTopic;
    protected $_notificationsManager;
    protected $save;

    public function let(
        AdminQueue $adminQueue,
        Mocks\Minds\Core\Entities $entities,
        Mocks\Minds\Core\Entities\Factory $entitiesFactory,
        Core\EntitiesBuilder $entitiesBuilder,
        PropagateRejectionDelegate $propagateRejectionDelegate,
        ActionEventsTopic $actionEventsTopic,
        NotificationsManager $notificationsManager,
        Save $save
    ) {
        // AdminQueue

        Di::_()->bind('Groups\AdminQueue', function () use ($adminQueue) {
            return $adminQueue->getWrappedObject();
        });

        $this->adminQueueMock = $adminQueue;

        // Entities

        Di::_()->bind('Entities', function () use ($entities) {
            return $entities->getWrappedObject();
        });

        $this->_entities = $entities;

        // Entities Factory

        Di::_()->bind('Entities\Factory', function () use ($entitiesFactory) {
            return $entitiesFactory->getWrappedObject();
        });

        $this->_entitiesFactory = $entitiesFactory;

        $this->_entitiesBuilder = $entitiesBuilder;

        $this->_propagateRejectionDelegate = $propagateRejectionDelegate ?? new PropagateRejectionDelegate();


        $this->_actionEventsTopic = $actionEventsTopic;

        $this->_notificationsManager = $notificationsManager;
        $this->save = $save;

        $this->beConstructedWith($entitiesBuilder, $propagateRejectionDelegate, $actionEventsTopic, $notificationsManager, null, $save);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Groups\Feeds');
    }

    // setGroup()

    public function it_should_set_group(Group $group)
    {
        $this
            ->setGroup($group)
            ->shouldReturn($this);
    }

    // getAll()

    public function it_should_get_all(
        Group $group,
    ) {
        $activity1 = (new Activity())->set('guid', '123');
        $activity2 = (new Activity())->set('guid', '456');

        $this->adminQueueMock->getAll($group, [], null)
            ->shouldBeCalled()
            ->willYield([
                $activity1,
                $activity2
            ]);

        $return = $this
            ->setGroup($group)
            ->getAll([]);

        $return->shouldBeAnArrayOf(2, Activity::class);
    }

    public function it_should_return_an_empty_array_during_get_all(
        Group $group
    ) {
        $this->adminQueueMock->getAll($group, [], null)
            ->shouldBeCalled()
            ->willYield([]);

        $return = $this
            ->setGroup($group)
            ->getAll([]);

        $return->shouldBeAnArrayOf(0, Activity::class);
    }

    public function it_should_throw_during_get_all_if_no_group()
    {
        $this->adminQueueMock->getAll(Argument::any())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringGetAll([]);
    }

    // count()

    public function it_should_count(
        Group $group
    ) {
        $this->adminQueueMock->count($group)
            ->shouldBeCalled()
            ->willReturn([
                [ 'count' => new Mocks\Cassandra\Value(2) ]
            ]);

        $this
            ->setGroup($group)
            ->count()
            ->shouldReturn(2);
    }

    public function it_should_count_zero_if_no_rows(
        Group $group
    ) {
        $this->adminQueueMock->count($group)
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->setGroup($group)
            ->count()
            ->shouldReturn(0);
    }

    public function it_should_throw_during_count_if_no_group()
    {
        $this->adminQueueMock->count(Argument::any())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringCount();
    }

    // queue()

    public function it_should_queue(
        Group $group,
        Activity $activity
    ) {
        $activity->get('guid')->willReturn(5000);
        $activity->getOwnerGuid()->willReturn(123);

        $this->adminQueueMock->add($group, $activity)
            ->shouldBeCalled()
            ->willReturn(true);


        $this
            ->setGroup($group)
            ->queue($activity, [ 'notification' => false ])
            ->shouldReturn(true);
    }

    public function it_should_throw_during_queue_if_no_group(
        Activity $activity
    ) {
        $activity->get('guid')->willReturn(5000);

        $this->adminQueueMock->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringQueue($activity, [ 'notification' => false ]);
    }

    public function it_should_throw_during_queue_if_no_activity(
        Group $group,
        Activity $activity
    ) {
        $activity->get('guid')->willReturn('');

        $this->adminQueueMock->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setGroup($group)
            ->shouldThrow(\Exception::class)
            ->duringQueue($activity, [ 'notification' => false ]);
    }

    // approve()

    public function it_should_approve(
        Group $group,
        Activity $activity,
        Entities\Image $attachment
    ) {
        $group->getGuid()->willReturn(1000);
        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1000);
        $activity->get('owner_guid')->willReturn(10000);
        $activity->get('entity_guid')->willReturn(8888);

        $activity->setPending(false)
            ->shouldBeCalled();

        $this->save->setEntity($activity)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save(true)
            ->shouldBeCalled();

        $this->adminQueueMock->delete($group, $activity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->_entitiesBuilder->single(8888)
            ->shouldBeCalled()
            ->willReturn($attachment);

        $attachment->get('subtype')
            ->shouldBeCalled()
            ->willReturn('image');

        $attachment->getWireThreshold()
            ->shouldBeCalled()
            ->willReturn(null);

        $attachment->set('access_id', 2)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->save->setEntity($attachment)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save(true)
            ->shouldBeCalled();

        $this
            ->setGroup($group)
            ->approve($activity, [ 'notification' => false ])
            ->shouldReturn(true);
    }

    public function it_should_throw_during_approve_if_no_group(
        Activity $activity
    ) {
        $this->adminQueueMock->delete(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringApprove($activity, [ 'notification' => false ]);
    }

    public function it_should_throw_during_approve_if_no_activity(
        Group $group,
        Activity $activity
    ) {
        $activity->get('guid')->willReturn('');

        $this->adminQueueMock->delete(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setGroup($group)
            ->shouldThrow(\Exception::class)
            ->duringApprove($activity, [ 'notification' => false ]);
    }

    public function it_should_throw_during_approve_if_activity_doesnt_belong_to_group(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);

        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1001);

        $this->adminQueueMock->delete(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setGroup($group)
            ->shouldThrow(\Exception::class)
            ->duringApprove($activity, [ 'notification' => false ]);
    }

    // reject()

    public function it_should_reject(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);
        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1000);
        $activity->get('owner_guid')->willReturn(123);

        $this->_propagateRejectionDelegate->onReject($activity)
            ->shouldBeCalled();

        $this->adminQueueMock->delete($group, $activity)
            ->shouldBeCalled()
            ->willReturn(true);


        $this->_notificationsManager->add(Argument::that(function ($notification) {
            return true;
        }))
            ->willReturn(true);

        $this
            ->setGroup($group)
            ->reject($activity, [ 'notification' => false ])
            ->shouldReturn(true);
    }

    public function it_should_throw_during_reject_if_no_group(
        Activity $activity
    ) {
        $this->adminQueueMock->delete(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringReject($activity, [ 'notification' => false ]);
    }

    public function it_should_throw_during_reject_if_no_activity(
        Group $group,
        Activity $activity
    ) {
        $activity->get('guid')->willReturn('');

        $this->adminQueueMock->delete(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setGroup($group)
            ->shouldThrow(\Exception::class)
            ->duringReject($activity, [ 'notification' => false ]);
    }

    public function it_should_throw_during_reject_if_activity_doesnt_belong_to_group(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);

        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1001);

        $this->adminQueueMock->delete(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->setGroup($group)
            ->shouldThrow(\Exception::class)
            ->duringReject($activity, [ 'notification' => false ]);
    }

    // approveAll()

    public function it_should_approve_all(
        Group $group,
        Activity $activity_1,
        Activity $activity_2,
        Entities\Video $attachment_1
    ) {
        $group->getGuid()->willReturn(1000);

        $activity_1->get('guid')->willReturn(5001);
        $activity_1->get('container_guid')->willReturn(1000);
        $activity_1->get('owner_guid')->willReturn(10000);
        $activity_1->get('entity_guid')->willReturn(8888);

        $activity_2->get('guid')->willReturn(5002);
        $activity_2->get('container_guid')->willReturn(1000);
        $activity_2->get('owner_guid')->willReturn(10000);
        $activity_2->get('entity_guid')->willReturn(null);

        $this->_entitiesBuilder->single(8888)
            ->shouldBeCalled()
            ->willReturn($attachment_1);

        $attachment_1->get('subtype')
            ->shouldBeCalled()
            ->willReturn('video');

        $attachment_1->getWireThreshold()
            ->shouldBeCalled()
            ->willReturn(null);

        $attachment_1->set('access_id', 2)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->save->setEntity($attachment_1)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save(true)
            ->shouldBeCalled();

        $this->adminQueueMock->getAll($group, [], null)
            ->shouldBeCalled()
            ->willYield([
                $activity_1->getWrappedObject(),
                $activity_2->getWrappedObject(),
            ]);


        // approve()

        $activity_1->setPending(false)
            ->shouldBeCalled();

        $this->save->setEntity($activity_1)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save(true)
            ->shouldBeCalled();

        $activity_2->setPending(false)
            ->shouldBeCalled();

        $this->save->setEntity($activity_2)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save(true)
            ->shouldBeCalled();

        $this->adminQueueMock->delete($group, Argument::type(Activity::class))
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        //

        $this
            ->setGroup($group)
            ->approveAll()
            ->shouldReturn([
                5001 => true,
                5002 => true
            ]);
    }

    public function it_should_throw_during_approve_all_if_no_group()
    {
        $this->adminQueueMock->getAll(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringApproveAll();
    }

    //

    public function getMatchers(): array
    {
        $matchers = [];

        $matchers['haveKeys'] = function ($subject, array $keys) {
            $valid = true;

            foreach ($keys as $key) {
                $valid = $valid && array_key_exists($key, $subject);
            }

            return $valid;
        };

        $matchers['beAnArrayOf'] = function ($subject, $count, $class) {
            if (!is_array($subject) || ($count !== null && count($subject) !== $count)) {
                throw new FailureException("Subject should be an array of $count elements");
            }

            $validTypes = true;

            foreach ($subject as $element) {
                if (!($element instanceof $class)) {
                    $validTypes = false;
                    break;
                }
            }

            if (!$validTypes) {
                throw new FailureException("Subject should be an array of {$class}");
            }

            return true;
        };

        return $matchers;
    }
}
