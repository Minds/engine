<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

class _7dTimespan extends AbstractTimespan
{
    /** @var string */
    protected $id = '7d';

    /** @var string */
    protected $label = 'Last 7 days';

    /** @var string */
    protected $interval = 'day';

    /** @var int */
    protected $fromTsMs;

    /** @var int */
    protected $comparisonInterval = 7;

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight 7 days ago') * 1000;
    }
}
