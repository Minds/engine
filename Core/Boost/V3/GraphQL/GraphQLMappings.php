<?php
namespace Minds\Core\Boost\V3\GraphQL;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addTypeNamespace('Minds\Core\Boost\V3\GraphQL');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\BoostEdge::class,
            Types\BoostNode::class,
        ]));
    }
}
