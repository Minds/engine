<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs\Controllers;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Configs\Manager;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfigInput;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Controller for MultiTenant Configs.
 */
class Controller
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Logger $logger
    ) {
    }

    /**
     * Gets multi-tenant config for the calling tenant.
     * @throws GraphQLException
     * @return MultiTenantConfig
     */
    #[Query]
    public function getMultiTenantConfig(): ?MultiTenantConfig
    {
        return $this->manager->getConfigs();
    }

    /**
     * Sets multi-tenant config for the calling tenant.
     * @param MultiTenantConfigInput $multiTenantConfigInput - input type with fields to change.
     * @throws GraphQLException - on error.
     * @return bool - true on success.
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function multiTenantConfig(
        MultiTenantConfigInput $multiTenantConfigInput,
        #[InjectUser] User $loggedInUser // Do not add in docblock as it will break GraphQL
    ): bool {
        return $this->manager->upsertConfigs(
            siteName: $multiTenantConfigInput->siteName,
            colorScheme: $multiTenantConfigInput->colorScheme,
            primaryColor: $multiTenantConfigInput->primaryColor,
            communityGuidelines: $multiTenantConfigInput->communityGuidelines,
            federationDisabled: $multiTenantConfigInput->federationDisabled,
            nsfwEnabled: $multiTenantConfigInput->nsfwEnabled,
        );
    }
}
