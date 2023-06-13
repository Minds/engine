<?php

namespace Spec\Minds\Mocks\Cassandra;

use ArrayAccess;
use Iterator;

/**
 * A class to mock Cassandra rows
 */
class Rows implements ArrayAccess, Iterator
{
    public $_items = [];
    public $_pagingStateToken = '';
    public bool $_isLastPage = false;
    private int $position = 0;

    public function __construct(array $items, $pagingStateToken, $isLastPage = false)
    {
        $this->_items = $items;
        $this->_pagingStateToken = $pagingStateToken;
        $this->_isLastPage = $isLastPage;
    }

    public function getIterator(): Iterator
    {
        return call_user_func(function () {
            foreach ($this->_items as $key => $val) {
                yield $key => $val;
            }
        });
    }

    public function pagingStateToken(): mixed
    {
        return $this->_pagingStateToken;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_items[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->_items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->_items[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->_items[$offset]);
    }

    public function isLastPage(): bool
    {
        return $this->_isLastPage;
    }

    public function count(): int
    {
        return count($this->_items);
    }

    public function first(): mixed
    {
        return $this->_items[0];
    }

    public function current(): mixed
    {
        return $this->_items[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->_items[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
