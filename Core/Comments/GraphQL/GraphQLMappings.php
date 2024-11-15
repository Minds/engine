<?php
declare(strict_types=1);

namespace Minds\Core\Comments\GraphQL;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\\Core\\Comments\\GraphQL\\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\CommentNode::class,
            Types\CommentEdge::class
        ]));
    }
}
