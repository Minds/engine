<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Engagement;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class ReferralsMetric extends AbstractEngagementMetric
{
    /** @var string */
    protected $id = 'referrals';

    /** @var string */
    protected $label = 'Referrals';

    /** @var string */
    protected $description = "Number of comments you have received on your content";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'referral::total';
}
