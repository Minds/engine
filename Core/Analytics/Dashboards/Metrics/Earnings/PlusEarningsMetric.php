<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Earnings;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class PlusEarningsMetric extends AbstractEarningsMetric
{
    /** @var string */
    protected $id = 'earnings_plus';

    /** @var string */
    protected $label = 'Minds+ Content';

    /** @var string */
    protected $description = "Total earnings for the Minds+ Content";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'usd_earnings::plus';
}
