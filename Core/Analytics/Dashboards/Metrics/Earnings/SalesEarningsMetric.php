<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Earnings;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class SalesEarningsMetric extends AbstractEarningsMetric
{
    /** @var string */
    protected $id = 'earnings_sales';

    /** @var string */
    protected $label = 'Sales';

    /** @var string */
    protected $description = "Total earnings for the sales you have referred. You earn a 25% commission when your referrals purchase Plus, Pro or Minds Tokens.";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'usd_earnings::sales';
}
