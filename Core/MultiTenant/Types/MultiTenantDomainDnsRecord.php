<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Enums\DnsRecordEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class MultiTenantDomainDnsRecord
{
    public function __construct(
        #[Field] public string $name,
        #[Field] public DnsRecordEnum $type,
        #[Field] public string $value
    ) {
    }

}
