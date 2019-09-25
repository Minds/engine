<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

class MtdTimespan extends TimespanAbstract
{
    /** @var string */
    protected $id = 'mtd';

    /** @var string */
    protected $label = 'month to date';

    /** @var string */
    protected $interval = 'day';

    /** @var int */
    protected $fromTsMs;

    /** @var string */
    protected $aggInterval = 'month';

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight first day of this month') * 1000;
    }
}
