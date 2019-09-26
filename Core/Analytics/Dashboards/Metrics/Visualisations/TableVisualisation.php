<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

use Minds\Traits\MagicAttributes;

class TableVisualisation extends AbstractVisualisation
{
    use MagicAttributes;

    const DATE_FORMAT = "d-m-Y";

    /** @var string */
    private $type = 'table';

    /** @var array */
    private $rows;

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'type' => $this->type,
            'rows' => (array) $this->rows,
        ];
    }
}
