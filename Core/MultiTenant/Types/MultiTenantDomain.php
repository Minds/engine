<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Enums\MultiTenantCustomHostnameStatusEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class MultiTenantDomain
{
    /**
     * @param int $tenantId
     * @param string $domain
     * @param MultiTenantCustomHostnameStatusEnum $status
     * @param int $createdAt
     * @param string $cloudflareId
     * @param int|null $updatedAt
     */
    public function __construct(
        #[Field] public int $tenantId,
        #[Field] public string $domain,
        #[Field] public MultiTenantCustomHostnameStatusEnum $status,
        #[Field] public int $createdAt,
        public string $cloudflareId,
        #[Field] public ?int $updatedAt = null,
    ) {
    }
}
