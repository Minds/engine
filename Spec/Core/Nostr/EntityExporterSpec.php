<?php

namespace Spec\Minds\Core\Nostr;

use Minds\Core\Nostr\EntityExporter;
use Minds\Core\Nostr\Manager;
use Minds\Core\Nostr\NostrEvent;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EntityExporterSpec extends ObjectBehavior
{
    protected $managerMock;
    public function let(Manager $manager)
    {
        $this->beConstructedWith($manager);
        $this->managerMock = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EntityExporter::class);
    }

    public function it_should_return_nostr_events()
    {
        $filters = [
            'ids' => [ 'af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c' ],
        ];

        $nostrEvent1 = new NostrEvent();

        $this->managerMock->getNostrEvents(Argument::any())
            ->willReturn([
                $nostrEvent1,
            ]);

        $this->managerMock->getElasticNostrEvents(Argument::any(), 11)
            ->willReturn([]);

        $this->getNostrReq($filters)
            ->shouldYieldLike(new \ArrayIterator([
                $nostrEvent1,
            ]));
    }

    public function it_should_return_nostr_events_from_elasticsearch()
    {
        $filters = [
            'authors' => [ '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b' ],
        ];

        $nostrEvent1 = new NostrEvent();

        $this->managerMock->getNostrEvents(Argument::any())
            ->willReturn([
            ]);

        $this->managerMock->getElasticNostrEvents(Argument::any(), 12)
            ->willReturn([
                $nostrEvent1
            ]);

        $this->getNostrReq($filters)
            ->shouldYieldLike(new \ArrayIterator([
                $nostrEvent1,
            ]));
    }

    public function it_should_not_query_es_after_limit()
    {
        $filters = [
            'authors' => [ '36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b' ],
            'limit' => 2
        ];

        $nostrEvent1 = new NostrEvent();

        $this->managerMock->getNostrEvents(Argument::any())
            ->willReturn([
                $nostrEvent1,
                $nostrEvent1
            ]);

        $this->managerMock->getElasticNostrEvents(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->getNostrReq($filters)
        ->shouldYieldLike(new \ArrayIterator([
            $nostrEvent1,
            $nostrEvent1
        ]));
    }

    public function it_should_not_query_es_for_e_filters()
    {
        $filters = [
            '#e' => ['af5b356facc3cde02254a60effd7e299cb66efe1f4af8bafc52ec3f5413e8a0c'],
            'limit' => 2
        ];

        $nostrEvent1 = new NostrEvent();

        $this->managerMock->getNostrEvents(Argument::any())
            ->willReturn([
                $nostrEvent1
            ]);

        $this->managerMock->getElasticNostrEvents(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->getNostrReq($filters)
        ->shouldYieldLike(new \ArrayIterator([
            $nostrEvent1
        ]));
    }

    public function it_should_not_query_es_for_p_filters()
    {
        $filters = [
            '#p' => ['36cb1113be1c14ef3026f42b565f33702776a5255985b78a38233c996c22f46b'],
            'limit' => 2
        ];

        $nostrEvent1 = new NostrEvent();

        $this->managerMock->getNostrEvents(Argument::any())
            ->willReturn([
                $nostrEvent1
            ]);

        $this->managerMock->getElasticNostrEvents(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->getNostrReq($filters)
        ->shouldYieldLike(new \ArrayIterator([
            $nostrEvent1
        ]));
    }
}
