<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Analytics\Views;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Analytics\Views\Delegates\ViewsDelegate;
use Minds\Core\Feeds\Seen\Manager as FeedsSeenManager;
use Minds\Core\Guid;
use Minds\Entities\EntityInterface;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var ElasticRepository */
    protected $elasticRepository;

    /** @var FeedsSeenManager */
    protected $feedsSeenManager;

    public function __construct(
        $repository = null,
        $elasticRepository = null,
        $feedsSeenManager = null,
        private ?ViewsDelegate $viewsDelegate = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->elasticRepository = $elasticRepository ?: new ElasticRepository();
        $this->feedsSeenManager = $feedsSeenManager ?: new FeedsSeenManager();
        $this->viewsDelegate ??= new ViewsDelegate();
    }

    /**
     * @param View $view
     * @param EntityInterface|null $entity
     * @return bool
     * @throws Exception
     */
    public function record(View $view, ?EntityInterface $entity = null)
    {
        // Reset time fields and use current timestamp
        $view
            ->setYear(null)
            ->setMonth(null)
            ->setDay(null)
            ->setUuid(Guid::build())
            ->setTimestamp(time());

        // Mark the entity as 'seen'
        $entityGuid = (new Urn($view->getEntityUrn()))->getNss();
        $this->feedsSeenManager->seeEntities([$entityGuid]);

        // Add to repository
        $this->repository->add($view);

        if ($entity) {
            $this->viewsDelegate->onRecordView($view, $entity);
        }

        return true;
    }

    /**
     * Synchronise views from cassandra to elastic
     * @param array $opts
     * @return void
     * @throws Exception
     */
    public function syncToElastic($opts = [])
    {
        $opts = array_merge([
            'from' => null,
            'to' => null,
            'day' => 5,
            'month' => 6,
            'year' => 2019,
            'limit' => 1000,
            'offset' => '',
        ], $opts);

        while (true) {
            $result = $this->repository->getList($opts);

            $opts['offset'] = $result->getPagingToken();

            foreach ($result as $view) {
                $this->elasticRepository->add($view);
                yield $view;
            }

            if ($result->isLastPage()) {
                break;
            }
        }
        $this->elasticRepository->bulk(); // Save the final batch
    }
}
