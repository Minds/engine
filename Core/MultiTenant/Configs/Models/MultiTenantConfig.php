<?php

declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Models;

use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Helpers\SerializationHelper;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Multi-tenant config model.
 */
#[Type]
class MultiTenantConfig
{
    /**
     * @param bool $globalMode - Global mode will make the newsfeed show all posts on the network and
     * be auto enabled into post notifications
     * @param int $sessionTimeoutSecs - The time in seconds that a session should timeout after. -1 will use the application default.
     */
    public function __construct(
        #[Field] public readonly ?string $siteName = null,
        #[Field] public readonly ?string $siteEmail = null,
        #[Field] public readonly ?MultiTenantColorScheme $colorScheme = null,
        #[Field] public readonly ?string $primaryColor = null,
        #[Field] public readonly ?string $customScript = null,
        #[Field] public readonly ?bool $federationDisabled = false,
        #[Field] public readonly ?string $replyEmail = null,
        #[Field] public readonly ?bool $nsfwEnabled = null,
        #[Field] public readonly ?bool $boostEnabled = false,
        #[Field] public readonly ?bool $customHomePageEnabled = false,
        #[Field] public readonly ?string $customHomePageDescription = null,
        #[Field] public readonly ?bool $walledGardenEnabled = false,
        #[Field] public readonly ?bool $digestEmailEnabled = true,
        #[Field] public readonly ?bool $welcomeEmailEnabled = true,
        #[Field] public readonly ?string $loggedInLandingPageIdWeb = null,
        #[Field] public readonly ?string $loggedInLandingPageIdMobile = null,
        #[Field] public readonly ?bool $isNonProfit = false,
        public readonly ?string $bloomerangApiKey = null,
        #[Field] public readonly ?string $disableAccountDisclaimer = null,
        #[Field] public readonly ?string $deleteAccountDisclaimer = null,
        #[Field] public readonly ?bool $globalMode = false,
        #[Field] public readonly ?int $sessionTimeoutSecs = -1,
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

    /**
     * Handle deserialization (for example from Cache) without leaving
     * newly added properties uninitialized. Note that deserialization could
     * still fail if the property is not nullable and has no default value.
     * @return void
     */
    public function __wakeup(): void
    {
        $propertiesToInitialize = (new SerializationHelper())->getUnititializedProperties($this);
        foreach ($propertiesToInitialize as $propName => $propValue) {
            $this->{$propName} = $propValue;
        }
    }
}
