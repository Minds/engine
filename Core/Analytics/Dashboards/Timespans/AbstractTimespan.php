<?php
namespace Minds\Core\Analytics\Dashboards\Timespans;

use Minds\Traits\MagicAttributes;

/**
 * @method string getId()
 * @method string getLabel()
 * @method string getInterval()
 * @method int getComparisonInterval()
 * @method int getFromTsMs()
 */
abstract class AbstractTimespan
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

    /** @var string */
    protected $comparisonInterval = 'day';

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
            'comparison_interval' => (string) $this->comparisonInterval,
            'from_ts_ms' => (int) $this->fromTsMs,
        ];
    }
}
