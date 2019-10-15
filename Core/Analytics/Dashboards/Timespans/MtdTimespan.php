<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

class MtdTimespan extends AbstractTimespan
{
    /** @var string */
    protected $id = 'mtd';

    /** @var string */
    protected $label = 'Month to date';

    /** @var string */
    protected $interval = 'day';

    /** @var int */
    protected $fromTsMs;

    /** @var int */
    protected $comparisonInterval = 28;

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight first day of this month') * 1000;
    }
}
