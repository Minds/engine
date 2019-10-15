<?php
/**
 *
 */
namespace Minds\Core\Analytics\Dashboards\Filters;

use Minds\Traits\MagicAttributes;

class FilterOptions
{
    use MagicAttributes;

    /** @var FilterOptionsOption[] */
    private $options = [];

    /**
     * Set options
     * @param FilterOptionsOption $options
     * @return self
     */
    public function setOptions(FilterOptionsOption ...$options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Export
     * @param array $export
     * @return array
     */
    public function export(array $export = []): array
    {
        $options = [];
        foreach ($this->options as $option) {
            $options[] = $option->export();
        }
        return $options;
    }
}
