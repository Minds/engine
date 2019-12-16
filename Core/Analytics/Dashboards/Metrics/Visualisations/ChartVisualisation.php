<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

use Minds\Core\Analytics\Dashboards\Metrics\HistogramSegment;
use Minds\Traits\MagicAttributes;

class ChartVisualisation extends AbstractVisualisation
{
    use MagicAttributes;

    const DATE_FORMAT = "d-m-Y";

    /** @var string */
    private $type = 'chart';

    /** @var string */
    private $xLabel = 'Date';

    /** @var string */
    private $yLabel  = 'count';

    /** @var HistogramSegment[] */
    private $segments;

    /** @var array */
    private $buckets = [];

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        if (!$this->segments) { // TODO: show deprecated message as we should use segments now
            $this->segments = [
                (new HistogramSegment())
                    ->setBuckets($this->buckets),
            ];
        }

        return [
            'type' => $this->type,
            'segments' => array_map(function ($segment) {
                return $segment->export();
            }, $this->segments),
        ];
    }
}
