<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Traits\MagicAttributes;

/**
 * @method HistogramBucket setKey(string $key)
 * @method string getKey()
 * @method HistogramBucket setTimestampMs(int $ms)
 * @method int getTimestampMs()
 * @method HistogramBucket setValue(mixed $value)
 * @method mixed $value
 */
class HistogramBucket
{
    use MagicAttributes;

    /** @var string */
    protected $key;

    /** @var int */
    protected $timestampMs;

    /** @var mixed */
    protected $value;

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'key' => $this->key,
            'date' => date('c', $this->timestampMs / 1000),
            'value' => $this->value,
        ];
    }
}
