<?php

namespace Minds\Entities;

/**
 * A collection of validation errors
 */
class ValidationErrorCollection implements ExportableInterface
{
    /**
     * @var ValidationError[]
     */

    public function __construct(
        private array $items = []
    ) {
    }

    public function add(ValidationError $error): self
    {
        $this->items[] = $error;
        return $this;
    }

    public function count() : int
    {
        return count($this->items);
    }

    public function export(array $extras = []): array
    {
        return $this->items;
    }
}
