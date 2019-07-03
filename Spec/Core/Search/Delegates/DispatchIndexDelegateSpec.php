<?php

namespace Spec\Minds\Core\Search\Delegates;

use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Search\Delegates\DispatchIndexDelegate;
use Minds\Core\Search\Index as SearchIndex;
use Minds\Core\Search\RetryQueue\Repository as RetryQueueRepository;
use Minds\Core\Search\RetryQueue\RetryQueueEntry;
use Minds\Entities\Entity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DispatchIndexDelegateSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var SearchIndex */
    protected $searchIndex;

    /** @var RetryQueueRepository */
    protected $retryQueueRepository;

    function let(
        EventsDispatcher $eventsDispatcher,
        SearchIndex $searchIndex,
        RetryQueueRepository $retryQueue
    )
    {
        $this->beConstructedWith($eventsDispatcher, $searchIndex, $retryQueue);

        $this->eventsDispatcher = $eventsDispatcher;
        $this->searchIndex = $searchIndex;
        $this->retryQueueRepository = $retryQueue;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(DispatchIndexDelegate::class);
    }

    function it_should_index_and_delete(Entity $entity)
    {
        $this->searchIndex->index($entity)
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->get('guid')
            ->shouldBeCalled()
            ->willReturn('5000');

        $this->retryQueueRepository->delete(Argument::type(RetryQueueEntry::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->index($entity)
            ->shouldReturn(true);
    }

    function it_should_throw_during_index_and_requeue(Entity $entity, RetryQueueEntry $retryQueueEntry)
    {
        $this->searchIndex->index($entity)
            ->shouldBeCalled()
            ->willReturn(false);

        $entity->get('guid')
            ->shouldBeCalled()
            ->willReturn('5000');

        $this->retryQueueRepository->get('urn:entity:5000')
            ->shouldBeCalled()
            ->willReturn($retryQueueEntry);

        $retryQueueEntry->getRetries()
            ->shouldBeCalled()
            ->willReturn(3);

        $retryQueueEntry->setLastRetry(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($retryQueueEntry);

        $retryQueueEntry->setRetries(4)
            ->shouldBeCalled()
            ->willReturn($retryQueueEntry);

        $this->retryQueueRepository->add($retryQueueEntry)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDispatcher->trigger('search:index', 'all', [
            'entity' => $entity
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->index($entity)
            ->shouldReturn(false);
    }
}
