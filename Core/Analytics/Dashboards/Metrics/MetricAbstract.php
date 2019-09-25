<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Analytics\Dashboards\Timespans\TimespansCollection;
use Minds\Core\Di\Di;
use Minds\Traits\MagicAttributes;

/**
 * @method MetricAbstract setTimespansCollection(TimespansCollection $timespansCollection)
 * @method string getId()
 * @method string getLabel()
 */
abstract class MetricAbstract
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var string */
    protected $label;

    /** @var string[] */
    protected $permissions;

    /** @var MetricValues */
    protected $values;

    /** @var TimespansCollection */
    protected $timespansCollection;

    /** @var FiltersCollection */
    protected $filtersCollection;

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
            'permissions' => (array) $this->permissions,
            'values' => $this->values ? (array) $this->values->export() : null,
        ];
    }
}
