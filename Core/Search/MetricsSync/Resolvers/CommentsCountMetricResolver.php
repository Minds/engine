<?php
namespace Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Comments\Manager;
use Minds\Core\Trending\Aggregates;

class CommentsCountMetricResolver extends AbstractMetricResolver
{
    /** @var Manager */
    protected $commentsManager;

    /** @var Aggegates\Aggregate */
    protected $aggregator;

    /** @var string */
    protected $metricId = 'comments:count';

    public function __construct($commentsManager = null, $aggregator = null, $db = null)
    {
        parent::__construct($db);
        $this->commentsManager = $commentsManager ?? new Manager;
        $this->aggregator = $aggregator ?? new Aggregates\Comments;
    }

    /**
    * Set the type
    * @param string $type
    * @return MetricResolverInterface
    */
    public function setType(string $type): MetricResolverInterface
    {
        if ($type === 'user') {
            throw new \Exception('Can not perform comment count sync on a user');
        }
        return parent::setType($type);
    }

    /**
     * Return the total count
     * @param string $guid
     * @return int
     */
    protected function getTotalCount(string $guid): int
    {
        try {
            return $this->commentsManager->count($guid, null, true);
        } catch (Exception $e) {
            return 0;
        }
    }
}
