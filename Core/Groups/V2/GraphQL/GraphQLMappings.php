<?php
namespace Minds\Core\Groups\V2\GraphQL;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addTypeNamespace('Minds\Core\Groups\V2\GraphQL');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\GroupEdge::class,
            Types\GroupNode::class,
        ]));
    }
}
