<?php
declare(strict_types=1);

namespace Minds\Core\Security\Rbac;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Core\Security\Rbac\Models\Role;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Security\Rbac\Controllers');
        // $this->schemaFactory->addTypeNamespace('Minds\\Core\\MultiTenant\\Enums');
        // $this->schemaFactory->addTypeNamespace('Minds\\Core\\MultiTenant\\Types\\Factories');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Role::class,
        ]));

    }
}
