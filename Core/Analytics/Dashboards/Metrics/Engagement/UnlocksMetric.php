<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Engagement;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;
use Minds\Core\Analytics\Dashboards\Metrics\HistogramSegment;

class UnlocksMetric extends AbstractEngagementMetric
{
    public function __construct($es = null)
    {
        parent::__construct($es);
        $this->segments = [
            (new HistogramSegment())
                ->setAggField('unlocks:plus::total')
                ->setAggType('sum'),
            (new HistogramSegment())
                ->setAggField('unlocks:plus::share')
                ->setAggType('avg')
                ->setLabel('Share %'),
        ];
    }

    /** @var string */
    protected $id = 'unlocks';

    /** @var string */
    protected $label = 'Minds+ Unlocks';

    /** @var string */
    protected $description = "Number of times your Minds+ content has been unlocked";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'unlocks:plus::total';
}
