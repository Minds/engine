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
            Types\NewsfeedConnection::class,
            Types\ActivityEdge::class,
            Types\ActivityNode::class,
            Types\UserEdge::class,
            Types\UserNode::class,
            Types\FeedHighlightsEdge::class,
            Types\FeedHighlightsConnection::class,
            Types\PublisherRecsEdge::class,
            Types\PublisherRecsConnection::class,
        ]));
    }
}
