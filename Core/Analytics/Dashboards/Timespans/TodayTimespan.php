<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

/**
 * @method string getId()
 * @method string getLabel()
 * @method string getInterval()
 */
class TodayTimespan extends AbstractTimespan
{
    /** @var string */
    protected $id = 'today';

    /** @var string */
    protected $label = 'Today';

    /** @var string */
    protected $interval = 'day';

    /** @var int */
    protected $fromTsMs;

    /** @var int */
    protected $comparisonInterval = 1;

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight') * 1000;
    }
}
