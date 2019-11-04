<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Engagement;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class VotesUpMetric extends AbstractEngagementMetric
{
    /** @var string */
    protected $id = 'votes_up';

    /** @var string */
    protected $label = 'Votes up';

    /** @var string */
    protected $description = "Number of votes up you have received on your content";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'vote:up::total';
}
