<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\Http\Cloudflare\Enums\CustomHostnameStatusEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class MultiTenantDomain
{
    public function __construct(
        #[Field] public int $tenantId,
        #[Field] public string $domain,
        public string $cloudflareId,
        #[Field] public CustomHostnameStatusEnum $status = CustomHostnameStatusEnum::PENDING,
        #[Field] public ?MultiTenantDomainDnsRecord $dnsRecord = null,
        #[Field] public ?MultiTenantDomainDnsRecord $ownershipVerificationDnsRecord = null,
    ) {
    }

}
