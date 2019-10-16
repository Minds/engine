<?php
namespace Minds\Core\Analytics\Dashboards\Filters;

use Minds\Traits\MagicAttributes;

abstract class AbstractFilter
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var string */
    protected $label;

    /** @var string */
    protected $description;

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var FilterOptions */
    protected $options;

    /** @var string */
    protected $selectedOption;

    /**
     * Set the selected option and toggle if selected
     * @param string $selectedOptionId
     * @return self
     */
    public function setSelectedOption(string $selectedOptionId): self
    {
        $this->selectedOption = $selectedOptionId;
        foreach ($this->options->getOptions() as $k => $option) {
            if ($option->getId() === $selectedOptionId) {
                $option->setSelected(true);
            }
        }
        return $this;
    }

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
            'description' => (string) $this->description,
            'options' => (array) $this->options->export(),
        ];
    }
}
