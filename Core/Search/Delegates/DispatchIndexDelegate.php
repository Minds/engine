<?php
/**
 * DispatchIndexDelegate
 * @author edgebal
 */

namespace Minds\Core\Search\Delegates;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Search\Index as SearchIndex;
use Minds\Core\Search\RetryQueue\Repository as RetryQueueRepository;
use Minds\Core\Search\RetryQueue\RetryQueueEntry;

class DispatchIndexDelegate
{
    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var SearchIndex */
    protected $searchIndex;

    /** @var RetryQueueRepository */
    protected $retryQueueRepository;

    /**
     * DispatchIndexDelegate constructor.
     * @param EventsDispatcher $eventsDispatcher
     * @param SearchIndex $searchIndex
     * @param RetryQueueRepository $retryQueue
     */
    public function __construct(
        $eventsDispatcher = null,
        $searchIndex = null,
        $retryQueue = null
    )
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->searchIndex = $searchIndex ?: Di::_()->get('Search\Index');
        $this->retryQueueRepository = $retryQueue ?: new RetryQueueRepository();
    }

    /**
     * @param $entity
     * @return bool
     * @throws Exception
     */
    public function index($entity)
    {
        try {
            $success = (bool) $this->searchIndex->index($entity);
        } catch (Exception $e) {
            error_log("[DispatchIndexDelegate] {$e}");
            $success = false;
        }

        $urn = (string) (new Urn($entity->guid));

        if ($success) {
            $retryQueueEntry = new RetryQueueEntry();
            $retryQueueEntry
                ->setEntityUrn($urn)
                ->setLastRetry(time());

            $this->retryQueueRepository->delete($retryQueueEntry);
        } else {
            $retryQueueEntry = $this->retryQueueRepository->get($urn);
            $retries = $retryQueueEntry->getRetries() + 1;

            $retryQueueEntry
                ->setLastRetry(time())
                ->setRetries($retries);

            $this->retryQueueRepository->add($retryQueueEntry);

            if ($retries < 5) {
                $this->eventsDispatcher->trigger('search:index', 'all', [
                    'entity' => $entity
                ]);
            }
        }

        return $success;
    }
}
