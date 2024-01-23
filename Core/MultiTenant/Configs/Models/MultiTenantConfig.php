<?php

declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Models;

use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Multi-tenant config model.
 */
#[Type]
class MultiTenantConfig
{
    public function __construct(
        #[Field] public readonly ?string $siteName = null,
        #[Field] public readonly ?string $siteEmail = null,
        #[Field] public readonly ?MultiTenantColorScheme $colorScheme = null,
        #[Field] public readonly ?string $primaryColor = null,
        #[Field] public readonly ?string $communityGuidelines = null,
        #[Field] public readonly ?bool $federationDisabled = false,
        #[Field] public readonly ?string $replyEmail = null,
        #[Field] public readonly ?bool $nsfwEnabled = null,
        #[Field] public readonly ?int $updatedTimestamp = null,
        #[Field] public readonly ?int $lastCacheTimestamp = null
    ) {
    }

    /**
     * Whether federation can be enabled.
     * @return bool|null
     */
    #[Field]
    public function canEnableFederation(): bool
    {
        return (bool) Di::_()->get(MultiTenantBootService::class)->getTenant()?->domain;
    }
}
