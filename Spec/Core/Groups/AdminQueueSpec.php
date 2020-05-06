<?php

namespace Spec\Minds\Core\Groups;

use Minds\Entities\Activity;
use Minds\Entities\Group;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks;
use Minds\Core\Data\Cassandra;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Common\Repository\Response;
use Minds\Core\Blogs\Legacy\Entity;

interface ResponseElement
{
    public function getEntity();
}

class AdminQueueSpec extends ObjectBehavior
{
    protected $_client;

    /** @var ElasticManager */
    protected $_elasticManager;

    public function let(
        Cassandra\Client $client,
        ElasticManager $elasticManager
    ) {
        $this->_client = $client;
        $this->_elasticManager = $elasticManager;
        $this->beConstructedWith($client, $elasticManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Groups\AdminQueue');
    }

    // getAll()

    public function it_should_get_all(
        Group $group
    ) {
        $rows = new Mocks\Cassandra\Rows([], '');

        $group->getGuid()->willReturn(1000);

        $this->_client->request(Argument::that(function ($query) {
            return $query->build()['values'][0] == 'group:adminqueue:1000';
        }))
            ->shouldBeCalled()
            ->willReturn($rows);

        $this
            ->getAll($group)
            ->shouldReturn($rows);
    }

    public function it_should_throw_during_get_all_if_no_group()
    {
        $this->_client->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringGetAll(null);
    }

    // count()

    public function it_should_count(
        Group $group,
        ElasticManager $elasticManager,
        Response $topGetListResponse1
    ) {
        $elasticManager->count(Argument::that(function (array $opts) {
            return gettype($opts) === 'array';
        }))
            ->shouldBeCalled()
            ->willReturn(2);

        $this->count($group);
    }

    public function it_should_throw_during_count_if_no_group()
    {
        $this->_client->request(Argument::cetera())
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

        $this->_client->request(Argument::that(function ($query) {
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

        $this->_client->request(Argument::cetera())
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

        $this->_client->request(Argument::cetera())
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

        $this->_client->request(Argument::cetera())
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

        $this->_client->request(Argument::that(function ($query) {
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

        $this->_client->request(Argument::cetera())
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

        $this->_client->request(Argument::cetera())
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

        $this->_client->request(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(\Exception::class)
            ->duringDelete($group, $activity);
    }
}
