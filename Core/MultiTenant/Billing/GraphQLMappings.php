<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Billing;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

/**
 * GraphQL mappings for multi-tenant billing.
 */
class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\MultiTenant\Billing\Controllers');
        //$this->schemaFactory->addTypeNamespace('Minds\\Core\\MultiTenant\\Configs\\Enums');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\TenantBillingType::class,
        ]));
    }
}
