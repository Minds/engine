<?php

namespace Spec\Minds\Core\Channels\Delegates\Artifacts;

use Minds\Core\Channels\Delegates\Artifacts\EntityDelegate;
use Minds\Core\Channels\Snapshots\Repository;
use Minds\Core\Channels\Snapshots\Snapshot;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EntityDelegateSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var CassandraClient */
    protected $db;

    public function let(
        Repository $repository,
        CassandraClient $db
    ) {
        $this->beConstructedWith($repository, $db);
        $this->repository = $repository;
        $this->db = $db;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EntityDelegate::class);
    }

    public function it_should_snapshot()
    {
        $this
            ->snapshot(1000)
            ->shouldReturn(true);
    }

    public function it_should_restore()
    {
        $this
            ->restore(1000)
            ->shouldReturn(true);
    }


    public function it_should_hide()
    {
        $this
            ->hide(1000)
            ->shouldReturn(true);
    }

    public function it_should_delete()
    {
        $this->db->request(Argument::that(function (Custom $prepared) {
            $query = $prepared->build();

            return stripos($query['string'], 'delete from entities') !== false &&
                $query['values'] === ['1000'];
        }), true)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->delete(1000)
            ->shouldReturn(true);
    }
}
