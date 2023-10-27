<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Models;

use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

/**
 * Multi-tenant config input model.
 */
#[Input]
class MultiTenantConfigInput
{
    public function __construct(
        #[Field] public readonly ?string $siteName = null,
        #[Field] public readonly ?string $siteEmail = null,
        #[Field] public readonly ?MultiTenantColorScheme $colorScheme = null,
        #[Field] public readonly ?string $primaryColor = null
    ) {
    }
}
