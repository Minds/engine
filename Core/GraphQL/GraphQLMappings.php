<?php

namespace Minds\Core\GraphQL;

use Minds\Core\Router\Enums\ApiScopeEnum;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;
use Minds\Core\Config\GraphQL\GraphQLMappings as ConfigGrapghlMappings;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addTypeNamespace("Minds\\Core\\Router\\Enums");
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\KeyValuePair::class,
            Types\KeyValueType::class,
        ]));

        (new ConfigGrapghlMappings)->register();
    }
}
