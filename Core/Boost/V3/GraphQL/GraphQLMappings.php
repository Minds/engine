<?php
namespace Minds\Core\Boost\V3\GraphQL;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Boost\V3\GraphQL\Controllers');
        $this->schemaFactory->addNamespace('Minds\\Core\\Boost\\V3\\GraphQL\\Types');

        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\BoostEdge::class,
            Types\BoostNode::class,
            Types\BoostsConnection::class,
        ]));
    }
}
