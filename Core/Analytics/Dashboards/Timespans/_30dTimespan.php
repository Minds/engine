<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

class _30dTimespan extends AbstractTimespan
{
    /** @var string */
    protected $id = '30d';

    /** @var string */
    protected $label = 'Last 30 days';

    /** @var string */
    protected $interval = 'day';

    /** @var int */
    protected $fromTsMs;

    /** @var int */
    protected $comparisonInterval = 30;

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight 30 days ago') * 1000;
    }
}
