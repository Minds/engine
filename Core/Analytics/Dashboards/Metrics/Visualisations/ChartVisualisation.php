<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

use Minds\Traits\MagicAttributes;

class ChartVisualisation extends AbstractVisualisation
{
    use MagicAttributes;

    const DATE_FORMAT = "d-m-Y";

    /** @var string */
    private $type = 'chart';

    /** @var string */
    private $xLabel = 'Date';

    /** @var array */
    private $xValues;

    /** @var string */
    private $yLabel  = 'count';

    /** @var array */
    private $yValues;

    /** @var array */
    private $buckets = [];

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'type' => $this->type,
            'segments' => [
                [
                    'buckets' => (array) $this->buckets,
                ],
            ]
        ];
    }
}
