<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use ArrayAccess;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
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

    /**
     * @return int|null
     */
    #[Field]
    public function getTenantId(): ?int
    {
        if (!$this->offsetExists('tenantId')) {
            return null;
        }
        return $this->offsetGet('tenantId');
    }
}
