<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Engagement;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;
use Minds\Core\Analytics\Dashboards\Metrics\HistogramSegment;

class ReferralsMetric extends AbstractEngagementMetric
{
    public function __construct($es = null)
    {
        parent::__construct($es);
        $this->segments = [
            (new HistogramSegment())
                ->setAggField('referral::total')
                ->setAggType('sum'),
            (new HistogramSegment())
                ->setAggField('referral::active')
                ->setAggType('sum')
                ->setLabel('Active'),
        ];
    }

    /** @var string */
    protected $id = 'referrals';

    /** @var string */
    protected $label = 'Referrals';

    /** @var string */
    protected $description = "Earnings from content posted by users you referred to Minds";

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $aggField = 'referral::total';
}
