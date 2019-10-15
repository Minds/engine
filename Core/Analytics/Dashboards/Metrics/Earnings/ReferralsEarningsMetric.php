<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Earnings;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class ReferralsEarningsMetric extends AbstractEarningsMetric
{
    /** @var string */
    protected $id = 'earnings_referrals';

    /** @var string */
    protected $label = 'Referrals USD';

    /** @var string */
    protected $description = 'Referral earnings for PRO users';

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'usd_earnings::referrals';
}
