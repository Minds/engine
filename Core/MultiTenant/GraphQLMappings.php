<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant;

use Minds\Core\Di\Di;
use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\MultiTenant\Types\NetworkUser;
use Minds\Helpers\StringLengthValidators\UsernameLengthValidator;
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
            NetworkUser::class
        ]));

        $this->schemaFactory->setInputTypeValidator(new Types\Validators\TenantInputValidator());
        $this->schemaFactory->setInputTypeValidator(new Types\Validators\NetworkUserInputValidator(
            new UsernameLengthValidator(),
            Di::_()->get(MultiTenantDataService::class),
        ));
    }
}
