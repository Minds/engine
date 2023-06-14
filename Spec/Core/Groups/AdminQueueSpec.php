<?php

namespace Spec\Minds\Core\Groups;

use Minds\Entities\Activity;
use Minds\Entities\Group;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks;
use Minds\Core\Data\Cassandra;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;

class AdminQueueSpec extends ObjectBehavior
{
    protected $clientMock;
    protected $scrollMock;
    protected $entitiesBuilderMock;
    protected $aclMock;

    public function let(
        Cassandra\Client $client,
        Cassandra\Scroll $scroll,
        EntitiesBuilder $entitiesBuilder,
        ACL $acl,
    ) {
        $this->beConstructedWith(
            $client,
            $scroll,
            $entitiesBuilder,
            $acl,
        );
        $this->clientMock = $client;
        $this->scrollMock = $scroll;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->aclMock = $acl;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Groups\AdminQueue');
    }

    // getAll()

    public function it_should_get_all(
        Group $group
    ) {
        $activity1 = new Activity();
        $activity2 = new Activity();

        $entities = [
            $activity1,
            $activity2,
        ];

        $group->getGuid()->willReturn(1000);

        $this->scrollMock->request(Argument::that(function ($query) {
            return $query->build()['values'][0] == 'group:adminqueue:1000';
        }), Argument::any())
            ->shouldBeCalled()
            ->willYield([
                [
                    'key' => 'group:adminqueue:1000',
                    'column1' => '123',
                    'value' => '123',
                ],
                [
                    'key' => 'group:adminqueue:1000',
                    'column1' => '456',
                    'value' => '456',
                ]
            ]);

        $this->entitiesBuilderMock->single(123)->shouldBeCalledOnce()->willReturn($activity1);
        $this->entitiesBuilderMock->single(456)->shouldBeCalledOnce()->willReturn($activity2);

        $this->aclMock->read(Argument::type(Activity::class))->willReturn(true);

        $this
            ->getAll($group, [])
            ->shouldYield(new \ArrayIterator($entities));
    }

    public function it_should_delete_from_queue_if_entity_not_found(
        Group $group
    ) {
        $activity1 = new Activity();

        $entities = [
            $activity1,
        ];

        $group->getGuid()->willReturn(1000);

        $this->scrollMock->request(Argument::that(function ($query) {
            return $query->build()['values'][0] == 'group:adminqueue:1000';
        }), Argument::any())
            ->shouldBeCalled()
            ->willYield([
                [
                    'key' => 'group:adminqueue:1000',
                    'column1' => '123',
                    'value' => '123',
                ],
                [
                    'key' => 'group:adminqueue:1000',
                    'column1' => '456',
                    'value' => '456',
                ]
            ]);

        $this->entitiesBuilderMock->single(123)->shouldBeCalledOnce()->willReturn($activity1);
        $this->entitiesBuilderMock->single(456)->shouldBeCalledOnce()->willReturn(null);

        $this->aclMock->read(Argument::type(Activity::class))->willReturn(true);

        $this->clientMock
            ->request(Argument::that(function ($query) {
                $prepared = $query->build();
                return
                    strpos($prepared['string'], 'DELETE FROM', 0) === 0 &&
                    $prepared['values'][0] == 'group:adminqueue:1000' &&
                    $prepared['values'][1] == '456';
            }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->getAll($group, [])
            ->shouldYield(new \ArrayIterator($entities));
    }

    public function it_should_delete_from_queue_if_acl_fails(
        Group $group
    ) {
        $activity1 = (new Activity())->set('guid', '123');
        $activity2 = (new Activity())->set('guid', '456');

        $entities = [
            $activity1
        ];

        $group->getGuid()->willReturn(1000);

        $this->scrollMock->request(Argument::that(function ($query) {
            return $query->build()['values'][0] == 'group:adminqueue:1000';
        }), Argument::any())
            ->shouldBeCalled()
            ->willYield([
                [
                    'key' => 'group:adminqueue:1000',
                    'column1' => '123',
                    'value' => '123',
                ],
                [
                    'key' => 'group:adminqueue:1000',
                    'column1' => '456',
                    'value' => '456',
                ]
            ]);

        $this->entitiesBuilderMock->single('123')->shouldBeCalledOnce()->willReturn($activity1);
        $this->entitiesBuilderMock->single('456')->shouldBeCalledOnce()->willReturn($activity2);

        $this->aclMock->read($activity1)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->aclMock->read($activity2)
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->clientMock
            ->request(Argument::that(function ($query) {
                $prepared = $query->build();
                return
                    strpos($prepared['string'], 'DELETE FROM', 0) === 0 &&
                    $prepared['values'][0] == 'group:adminqueue:1000' &&
                    $prepared['values'][1] == '456';
            }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->getAll($group, [])
            ->shouldYield(new \ArrayIterator($entities));
    }

    // count()

    public function it_should_count(
        Group $group
    ) {
        $rows = new Mocks\Cassandra\Rows([], '');

        $group->getGuid()->willReturn(1000);

        $this->clientMock->request(Argument::that(function ($query) {
            return $query->build()['values'][0] == 'group:adminqueue:1000';
        }))
            ->shouldBeCalled()
            ->willReturn($rows);

        $this
            ->count($group)
            ->shouldReturn($rows);
    }

    public function it_should_throw_during_count_if_no_group()
    {
        $this->clientMock->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringCount(null);
    }

    // add()

    public function it_should_add(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);
        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1000);

        $this->clientMock->request(Argument::that(function ($query) {
            return $query->build()['values'][0] == 'group:adminqueue:1000';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->add($group, $activity)
            ->shouldReturn(true);
    }

    public function it_should_throw_during_add_if_no_group(
        Activity $activity
    ) {
        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1000);

        $this->clientMock->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringAdd(null, $activity);
    }

    public function it_should_throw_during_add_if_no_activity(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);
        $activity->get('guid')->willReturn('');

        $this->clientMock->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringAdd($group, $activity);
    }

    public function it_should_throw_during_add_if_activity_doesnt_belong_to_group(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);
        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1001);

        $this->clientMock->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringAdd($group, $activity);
    }

    // add()

    public function it_should_delete(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);
        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1000);

        $this->clientMock->request(Argument::that(function ($query) {
            return $query->build()['values'][0] == 'group:adminqueue:1000';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->delete($group, $activity)
            ->shouldReturn(true);
    }

    public function it_should_throw_during_delete_if_no_group(
        Activity $activity
    ) {
        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1000);

        $this->clientMock->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringDelete(null, $activity);
    }

    public function it_should_throw_during_delete_if_no_activity(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);
        $activity->get('guid')->willReturn('');

        $this->clientMock->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringDelete(null, $activity);
    }

    public function it_should_throw_during_delete_if_activity_doesnt_belong_to_group(
        Group $group,
        Activity $activity
    ) {
        $group->getGuid()->willReturn(1000);
        $activity->get('guid')->willReturn(5000);
        $activity->get('container_guid')->willReturn(1001);

        $this->clientMock->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringDelete($group, $activity);
    }
}
