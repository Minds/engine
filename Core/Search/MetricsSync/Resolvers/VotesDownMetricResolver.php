<?php
namespace Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Trending\Aggregates;

class VotesDownMetricResolver extends AbstractVotesMetricResolver
{
    /** @var Aggegates\Aggregate */
    protected $aggregator;

    /** @var string */
    protected $counterMetricId = 'thumbs:down';

    /** @var string */
    protected $metricId = 'votes:down';

    public function __construct($counters = null, $aggregator = null)
    {
        parent::__construct($counters);
        $this->aggregator = $aggregator ?? new Aggregates\Votes();
    }
}
