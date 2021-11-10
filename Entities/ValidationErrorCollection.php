<?php

namespace Minds\Entities;

use Countable;
use Iterator;
use ArrayAccess;
use Minds\Exceptions\ServerErrorException;
use TypeError;

/**
 * A collection of validation errors
 */
class ValidationErrorCollection implements ExportableInterface, Iterator, Countable, ArrayAccess
{
    /**
     * @var ValidationError[]
     */
    private array $items = [];

    /**
     * @var int The current index for the iterator
     */
    private int $currentPosition = 0;

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

    public function current(): ValidationError
    {
        return $this->items[$this->currentPosition];
    }

    public function next(): void
    {
        $this->currentPosition++;
    }

    public function key(): int
    {
        return $this->currentPosition;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->currentPosition]);
    }

    public function rewind(): void
    {
        $this->currentPosition = 0;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): ValidationError
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (!($value instanceof ValidationError)) {
            throw new TypeError("Only objects of type ValidationError can be added to the collection");
        }

        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        array_splice($this->items, $offset, 1);
    }
}
