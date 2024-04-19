<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Models\Tenant;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class TenantLoginRedirectDetails
{
    public function __construct(
        #[Field] public Tenant $tenant,
        #[Field] public ?string $loginUrl = null,
        #[Field] public ?string $jwtToken = null
    ) {
    }
}
