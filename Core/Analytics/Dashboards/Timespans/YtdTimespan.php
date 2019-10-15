<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

class YtdTimespan extends AbstractTimespan
{
    /** @var string */
    protected $id = 'ytd';

    /** @var string */
    protected $label = 'Year to date';

    /** @var string */
    protected $interval = 'month';

    /** @var int */
    protected $fromTsMs;

    /** @var string */
    protected $comparisonInterval = 365;

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight first day of January') * 1000;
    }
}
