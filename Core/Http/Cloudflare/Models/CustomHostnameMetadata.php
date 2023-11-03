<?php
declare(strict_types=1);

namespace Minds\Core\Http\Cloudflare\Models;

use ArrayAccess;

class CustomHostnameMetadata implements ArrayAccess
{
    public function __construct(
        private array $metadata,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->metadata[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->metadata[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->metadata[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->metadata[$offset]);
    }

}
