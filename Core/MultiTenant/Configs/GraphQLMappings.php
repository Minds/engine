<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\MultiTenant\Configs\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\\Core\\MultiTenant\\Configs\\Enums');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Models\MultiTenantConfig::class,
            Models\MultiTenantConfigInput::class
        ]));
        $this->schemaFactory->setInputTypeValidator(new Validators\MultiTenantConfigInputValidator());
    }
}
