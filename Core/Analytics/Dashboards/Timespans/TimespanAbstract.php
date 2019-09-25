<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

use Minds\Traits\MagicAttributes;

/**
 * @method string getId()
 * @method string getLabel()
 * @method string getInterval()
 */
abstract class TimespanAbstract
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var string */
    protected $label;

    /** @var string */
    protected $interval;

    /** @var int */
    protected $fromTsMs;

    /** @var int */
    protected $previousFromTsMs;

    /** @var string */
    protected $aggInterval = 'day';

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export($extras = []): array
    {
        return [
            'id' => (string) $this->id,
            'label' => (string) $this->label,
            'interval' => (string) $this->interval,
            'agg_interval' => (string) $this->aggInterval,
            'from_ts_ms' => (int) $this->fromTsMs,
        ];
    }
}
