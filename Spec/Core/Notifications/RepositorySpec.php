<?php

namespace Spec\Minds\Core\Notifications;

use Minds\Core\Notifications\Repository;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Notifications\Notification;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;
use Cassandra\Timeuuid;
use Cassandra\Bigint;
use Cassandra\Timestamp;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    private $cql;

    public function let(Client $cql)
    {
        $this->beConstructedWith($cql);
        $this->cql = $cql;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_return_a_notification_from_uuid()
    {
        $uuid = (new Timeuuid())->uuid();

        $this->cql->request(Argument::that(function ($prepared) use ($uuid) {
            $values = $prepared->build()['values'];
            return $values[0]->value() == 123
                && $values[1]->uuid() == $uuid;
        }))
            ->willReturn(new Rows([
                [
                    'uuid' => new Timeuuid(time()),
                    'to_guid' => new Bigint(123),
                    'from_guid' => new Bigint(456),
                    'entity_guid' => '789',
                    'entity_urn' => 'urn:entity:789',
                    'created_timestamp' => new Timestamp(time()),
                    'read_timestamp' => null,
                    'type' => 'like',
                    'data' => '',
                ]
            ], ''));
        $notification = $this->get("urn:notification:123-$uuid");

        $notification->getToGuid()
            ->shouldBe(123);
        $notification->getFromGuid()
            ->shouldBe(456);
        $notification->getEntityGuid()
            ->shouldBe('789');
        $notification->getEntityUrn()
            ->shouldBe('urn:entity:789');
        $notification->getCreatedTimestamp()
            ->shouldBe(time());
        $notification->getType()
            ->shouldBe('like');
    }

    public function it_should_add_to_database(Notification $notification)
    {
        $uuid = (new Timeuuid())->uuid();

        $this->cql->request(Argument::that(function ($prepared) use ($uuid) {
            $values = $prepared->build()['values'];
            return $values[0]->value() == 123
            && $values[1]->uuid() == $uuid;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $notification->getToGuid()
            ->willReturn(123);
        $notification->getUuid()
            ->willReturn($uuid);
        $notification->getFromGuid()
            ->willReturn(456);
        $notification->getType()
            ->willReturn('boost');
        $notification->getEntityGuid()
            ->willReturn(789);
        $notification->getEntityUrn()
            ->willReturn('urn:entity:789');
        $notification->getCreatedTimestamp()
            ->willReturn(time());
        $notification->getReadTimestamp()
            ->willReturn(null);
        $notification->getData()
            ->willReturn(null);

        $this->add($notification)
            ->shouldNotReturn(false);
    }

    public function it_should_load_notification_for_user()
    {
        $this->cql->request(Argument::that(function ($prepared) {
            $statement = preg_replace('/\s+/', ' ', $prepared->build()['string']);
            $values = $prepared->build()['values'];
            return strpos($statement, 'SELECT * FROM notifications WHERE to_guid', 0) !== false
                && $values[0]->value() == 123;
        }))
            ->willReturn(new Rows([
                [
                    'uuid' => new Timeuuid(time()),
                    'to_guid' => new Bigint(123),
                    'from_guid' => new Bigint(456),
                    'entity_guid' => '789',
                    'entity_urn' => 'urn:entity:789',
                    'created_timestamp' => new Timestamp(time()),
                    'read_timestamp' => null,
                    'type' => 'like',
                    'data' => '',
                ]
            ], ''));

        $notifications = $this->getList([
            'limit' => 24,
            'to_guid' => 123,
        ]);
        $notifications->shouldHaveCount(1);
    }

    public function it_should_load_notification_from_type_group()
    {
        $this->cql->request(Argument::that(function ($prepared) {
            $statement = $prepared->build()['string'];
            $values = $prepared->build()['values'];
            return strpos($statement, 'SELECT * FROM notifications_by_type_group', 0) !== false
                && $values[0]->value() == 123
                && $values[1] == 'votes';
        }))
            ->willReturn(new Rows([
                [
                    'uuid' => new Timeuuid(time()),
                    'to_guid' => new Bigint(123),
                    'from_guid' => new Bigint(456),
                    'entity_guid' => '789',
                    'entity_urn' => 'urn:entity:789',
                    'created_timestamp' => new Timestamp(time()),
                    'read_timestamp' => null,
                    'type' => 'like',
                    'data' => '',
                ]
            ], ''));

        $notifications = $this->getList([
            'limit' => 24,
            'to_guid' => 123,
            'type_group' => 'votes',
        ]);
        $notifications->shouldHaveCount(1);
    }
}
