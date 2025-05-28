<?php
namespace Minds\Core\Groups\V2\GraphQL;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Groups\V2\GraphQL\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\GroupEdge::class,
            Types\GroupNode::class,
        ]));
    }
}
