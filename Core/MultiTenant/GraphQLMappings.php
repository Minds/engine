<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Types\MultiTenantDomain;
use Minds\Core\MultiTenant\Types\MultiTenantDomainDnsRecord;
use Minds\Core\MultiTenant\Types\TenantUser;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\MultiTenant\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\\Core\\MultiTenant\\Enums');
        $this->schemaFactory->addTypeNamespace('Minds\\Core\\MultiTenant\\Types\\Factories');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Tenant::class,
            TenantUser::class,
            MultiTenantDomain::class,
            MultiTenantDomainDnsRecord::class,
        ]));

        $this->schemaFactory->setInputTypeValidator(new Types\Validators\TenantInputValidator());
        $this->schemaFactory->setInputTypeValidator(new Types\Validators\TenantUserInputValidator());
    }
}
