<?php
/**
 *
 */
namespace Minds\Core\Analytics\Dashboards\Filters;

use Minds\Traits\MagicAttributes;

class FilterOptionsOption
{
    use MagicAttributes;

    /** @var string */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $description;

    /** @var bool */
    private $available = true;

    /** @var bool */
    private $selected = false;

    /**
     * Export
     * @param array $export
     * @return array
     */
    public function export(array $export = []): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'available' => (bool) $this->available,
            'selected' => (bool) $this->selected,
        ];
    }
}
