<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Featured user node. Can be used in a connection.
 */
#[Type]
class FeaturedUser extends FeaturedEntity
{
    public function __construct(
        #[Field(outputType: 'String!')] public readonly int $tenantId,
        #[Field(outputType: 'String!')] public readonly int $entityGuid,
        #[Field] public readonly bool $autoSubscribe,
        #[Field] public readonly bool $recommended,
        #[Field] public readonly ?string $username,
        private readonly ?string $name = null,
    ) {
    }

    /**
     * Gets user's display name, or username.
     * @return string user's display name, or username.
     */
    #[Field]
    public function getName(): string {
        return $this->name ?? $this->username ?? '';
    }
}
