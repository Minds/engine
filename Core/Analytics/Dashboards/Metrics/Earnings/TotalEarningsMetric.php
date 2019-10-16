<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Earnings;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class TotalEarningsMetric extends AbstractEarningsMetric
{
    /** @var string */
    protected $id = 'earnings_total';

    /** @var string */
    protected $label = 'Total Earnings';

    /** @var string */
    protected $description = 'Total earnings for the selected timespan.';

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'usd_earnings::total';
}
