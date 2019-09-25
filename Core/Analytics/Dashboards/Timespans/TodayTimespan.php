<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

/**
 * @method string getId()
 * @method string getLabel()
 * @method string getInterval()
 */
class TodayTimespan extends TimespanAbstract
{
    /** @var string */
    protected $id = 'today';

    /** @var string */
    protected $label = 'today';

    /** @var string */
    protected $interval = 'day';

    /** @var int */
    protected $fromTsMs;

    /** @var string */
    protected $aggInterval = 'day';

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight') * 1000;
    }
}
