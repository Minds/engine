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

    public function offsetExists($offset)
    {
        return isset($this->_items[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_items[$offset] = $value;
    }

    public function offsetUnset($offset)
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

    public function current()
    {
        return $this->_items[$this->position];
    }

    public function next()
    {
        $this->position++;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->_items[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}
