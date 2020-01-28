<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

class _90dTimespan extends AbstractTimespan
{
    /** @var string */
    protected $id = '90d';

    /** @var string */
    protected $label = 'Last 90 days';

    /** @var string */
    protected $interval = 'day';

    /** @var int */
    protected $fromTsMs;

    /** @var int */
    protected $comparisonInterval = 90;

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight 90 days ago') * 1000;
    }
}
