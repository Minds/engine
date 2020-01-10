<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Engagement;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class ReferralsActiveMetric extends AbstractEngagementMetric
{
    /** @var string */
    protected $id = 'referrals_active';

    /** @var string */
    protected $label = 'Active Referrals';

    /** @var string */
    protected $description = "Referred users who are active for more than 50% of their first 7 days";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'referral::active';
}
