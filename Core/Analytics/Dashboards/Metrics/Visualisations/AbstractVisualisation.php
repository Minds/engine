<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

use Minds\Traits\MagicAttributes;

abstract class AbstractVisualisation implements VisualisationInterface
{
    use MagicAttributes;

    /** @var string */
    private $type;

    public function export(array $extras = []): array
    {
        return [
        ];
    }
}
