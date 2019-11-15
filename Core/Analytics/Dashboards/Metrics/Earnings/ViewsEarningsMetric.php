<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Earnings;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class ViewsEarningsMetric extends AbstractEarningsMetric
{
    /** @var string */
    protected $id = 'earnings_views';

    /** @var string */
    protected $label = 'Pageviews';

    /** @var string */
    protected $description = "Total earnings for the pageviews on your channel's assets. You earn $1 for every 1,000 pageviews.";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'usd_earnings::views';
}
