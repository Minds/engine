<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

class _1yTimespan extends AbstractTimespan
{
    /** @var string */
    protected $id = '1y';

    /** @var string */
    protected $label = '1 year ago';

    /** @var string */
    protected $interval = 'month';

    /** @var int */
    protected $fromTsMs;

    /** @var int */
    protected $comparisonInterval = 365;

    public function __construct()
    {
        $this->fromTsMs = strtotime('midnight 365 days ago') * 1000;
    }
}
