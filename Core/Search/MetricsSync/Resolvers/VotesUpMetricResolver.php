<?php
namespace Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Trending\Aggregates;

class VotesUpMetricResolver extends AbstractVotesMetricResolver
{
    /** @var Aggegates\Aggregate */
    protected $aggregator;

    /** @var string */
    protected $counterMetricId = 'thumbs:up';

    /** @var string */
    protected $metricId = 'votes:up';

    public function __construct($counters = null, $aggregator = null)
    {
        parent::__construct($counters);
        $this->aggregator = $aggregator ?? new Aggregates\Votes();
    }
}
