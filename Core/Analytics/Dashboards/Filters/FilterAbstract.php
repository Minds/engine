<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

use Minds\Traits\MagicAttributes;

abstract class FilterAbstract
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var string */
    protected $label;

    /** @var FilterOptions */
    protected $options;

    /** @var string */
    protected $selectedOption;

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
            'options' => (array) $this->options->export(),
        ];
    }
}
