<?php
namespace Minds\Core\Feeds\GraphQL;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Feeds\GraphQL\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\Edges\ActivityEdge::class,
            Types\Edges\ActivityNode::class,
            Types\Edges\UserNode::class,
        ]));
    }
}
