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
    public $_isLastPage = false;
    private int $position = 0;

    public function __construct(array $items, $pagingStateToken, $isLastPage = false)
    {
        $this->_items = $items;
        $this->_pagingStateToken = $pagingStateToken;
        $this->_isLastPage = $isLastPage;
    }

    public function getIterator()
    {
        return call_user_func(function () {
            foreach ($this->_items as $key => $val) {
                yield $key => $val;
            }
        });
    }

    public function pagingStateToken()
    {
        return $this->_pagingStateToken;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->_items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->_items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->_items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->_items[$offset]);
    }

    public function isLastPage()
    {
        return $this->_isLastPage;
    }

    public function count()
    {
        return count($this->_items);
    }

    public function first()
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

    public function key(): string
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
