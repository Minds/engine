<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Enums\CustomHostnameStatusEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Domain
{
    /**
     * @param int $tenantId
     * @param string $domain
     * @param CustomHostnameStatusEnum $status
     * @param int $createdAt
     * @param string $cloudflareId
     * @param int|null $updatedAt
     */
    public function __construct(
        #[Field] public int $tenantId,
        #[Field] public string $domain,
        #[Field] public CustomHostnameStatusEnum $status,
        #[Field] public int $createdAt,
        public string $cloudflareId,
        #[Field] public ?int $updatedAt = null,
    ) {
    }
}
