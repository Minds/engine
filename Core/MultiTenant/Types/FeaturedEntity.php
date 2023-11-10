<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Featured entity node, can be used in a connection.
 * Intended to be extended by returned entities rather than
 * returned directly.
 */
#[Type]
class FeaturedEntity implements NodeInterface
{
    public function __construct(
        #[Field(outputType: 'String!')] public readonly int $tenantId,
        #[Field(outputType: 'String!', inputType: 'String!')] public readonly int $entityGuid,
        #[Field] public readonly bool $autoSubscribe,
        #[Field] public readonly bool $recommended,
        private readonly ?string $name = null,
    ) {
    }

    /**
     * Gets entity name.
     * @return string entity name.
     */
    #[Field]
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Gets ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    public function getId(): ID
    {
        return new ID($this->tenantId . '-' . $this->entityGuid);
    }
}
