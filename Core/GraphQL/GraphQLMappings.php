<?php

namespace Minds\Core\GraphQL;

use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\KeyValuePair::class,
            Types\KeyValueType::class
        ]));
    }
}
