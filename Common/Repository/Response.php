<?php

/**
 * Minds Repository Response
 *
 * @author emi
 */

namespace Minds\Common\Repository;

use Exception;

class Response implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    /** @var array */
    protected $data = [];

    /** @var string */
    protected $pagingToken;

    /** @var Exception */
    protected $exception;

    /** @var bool */
    protected $lastPage = false;

    public function __construct(array $data = null, $pagingToken = null, $lastPage = null)
    {
        if ($data !== null) {
            $this->data = $data;
        }

        if ($pagingToken !== null) {
            $this->pagingToken = $pagingToken;
        }

        if ($lastPage !== null) {
            $this->lastPage = $lastPage;
        }
    }

    /**
     * Sets the paging token for this result set
     * @param string|null $pagingToken
     * @return Response
     */
    public function setPagingToken(?string $pagingToken): self
    {
        $this->pagingToken = $pagingToken;
        return $this;
    }

    /**
     * Gets the paging token for this result set
     * @return string|null
     */
    public function getPagingToken(): ?string
    {
        return $this->pagingToken;
    }

    /**
     * Sets the exception for a faulty result set
     * @param Exception $exception
     * @return Response
     */
    public function setException(Exception $exception): self
    {
        $this->exception = $exception;
        return $this;
    }

    /**
     * Gets the exception for a faulty result set
     * @return Exception
     */
    public function getException(): Exception
    {
        return $this->exception;
    }

    /**
     * Sets the flag for a last page of a response
     * @param bool $lastPage
     * @return Response
     */
    public function setLastPage(bool $lastPage): self
    {
        $this->lastPage = $lastPage;
        return $this;
    }

    /**
     * Returns if it's the last page of a response
     * @return bool
     */
    public function isLastPage(): bool
    {
        return !!$this->lastPage;
    }

    /**
     * Returns if the result set is faulty
     * @return bool
     */
    public function hasFailed(): bool
    {
        return !!$this->exception;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current(): mixed
    {
        return current($this->data);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next(): void
    {
        next($this->data);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key(): mixed
    {
        return key($this->data);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind(): void
    {
        reset($this->data);
    }

    /**
     * Rewinds the Iterator to the first element and returns its value
     * @return mixed
     */
    public function reset(): mixed
    {
        return reset($this->data);
    }

    /**
     * Sets the pointer onto the last Iterator element and returns its value
     * @return mixed
     */
    public function end(): mixed
    {
        return end($this->data);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be cast to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
            return;
        }

        $this->data[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @param array $data
     * @return Response
     */
    public function pushArray(array $data): self
    {
        array_push($this->data, ...$data);
        return $this;
    }

    /**
     * Prepend items to the start of an array.
     * @param array $data
     * @return self
     */
    public function prependToArray(array $data): self
    {
        $this->data = [...$data, ...$this->data];
        return $this;
    }


    /**
     * Exports the data array
     * @return array
     */
    public function toArray(): array
    {
        return $this->data ?: [];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Returns a clone of this response with the inverse order
     * @param bool $preserveKeys
     * @return Response
     */
    public function reverse(bool $preserveKeys = false): self
    {
        return new self(array_reverse($this->data, $preserveKeys), $this->pagingToken, $this->lastPage);
    }

    /**
     * Iterates over each element passing them to the callback function.
     * If the callback function returns true, the element is returned into
     * the result Response.
     * @param callable $callback
     * @param bool $preserveKeys
     * @return Response
     */
    public function filter(callable $callback, bool $preserveKeys = false): self
    {
        $filtered = array_filter($this->data, $callback, ARRAY_FILTER_USE_BOTH);

        if (!$preserveKeys) {
            $filtered = array_values($filtered);
        }

        return new self($filtered, $this->pagingToken, $this->lastPage);
    }

    /**
     * Applies the callback to the elements and returns a clone of the Response
     * @param callable $callback
     * @return Response
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->data), $this->pagingToken);
    }

    /**
     * Iteratively reduce the Response to a single value using a callback function
     * @param callable $callback
     * @param mixed $initialValue
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initialValue = null): mixed
    {
        return array_reduce($this->data, $callback, $initialValue);
    }

    /**
     * @param callable $callback
     * @return Response
     */
    public function sort(callable $callback): Response
    {
        $data = $this->data;
        usort($data, $callback);

        return new self($data, $this->pagingToken);
    }

    /**
     * Returns the first element of the Response, or null if empty
     * @return mixed|null
     */
    public function first(): mixed
    {
        return $this->data[0] ?? null;
    }

    /**
     * Returns the last element
     * @return mixed | null
     */
    public function last(): mixed
    {
        $count = count($this->data);
        return $this->data[$count - 1] ?? null;
    }
}
