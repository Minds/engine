<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Traits\MagicAttributes;

/**
 * @method HistogramSegment setLabel(string $label)
 * @method string getLabel()
 * @method HistogramSegment setAggField(string $field)
 * @method string getAggField()
 * @method HistogramSegment setAggType(string $type)
 * @method string getAggType()
 * @method HistogramSegment setComparison(bool $is)
 * @method bool isComparison()
 * @method HistogramSegment setBuckets(array $buckets)
 * @method array getBuckets()
 */
class HistogramSegment
{
    use MagicAttributes;

    /** @var string */
    protected $label;

    /** @var string */
    protected $aggField;

    /** @var string */
    protected $aggType = 'sum';

    /** @var bool */
    protected $comparison = false;

    /** @var HistogramBucket[] */
    protected $buckets = [];

    /**
     * @param array
     * @return self
     */
    public function addBucket(HistogramBucket $bucket): self
    {
        $this->buckets[] = $bucket;
        return $this;
    }

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'label' => $this->label,
            'comparison' => (bool) $this->comparison,
            'buckets' => array_map(function ($bucket) {
                if (is_array($bucket)) { // TODO: throw deprecated error because should be HistogramBucket
                    return $bucket;
                }
                return $bucket->export();
            }, $this->buckets),
        ];
    }
}
