<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

class MaxTimespan extends AbstractTimespan
{
    /** @var string */
    protected $id = 'max';

    /** @var string */
    protected $label = 'Max';

    /** @var string */
    protected $interval = 'week';

    /** @var int */
    protected $fromTsMs;

    /** @var int */
    protected $comparisonInterval = 0;

    public function __construct()
    {
        $this->fromTsMs = 0;
    }
}
