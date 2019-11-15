<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Engagement;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

class SubscribersMetric extends AbstractEngagementMetric
{
    /** @var string */
    protected $id = 'subscribers';

    /** @var string */
    protected $label = 'Subscribers';

    /** @var string */
    protected $description = "Number of subscribers your channel has gained";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'subscribe::total';
}
