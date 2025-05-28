<?php
namespace Minds\Core\Feeds\GraphQL;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Feeds\GraphQL\Controllers');
        $this->schemaFactory->addNamespace('Minds\\Core\\Feeds\\GraphQL\\Enums');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\ActivityNode::class,
            Types\NewsfeedConnection::class,
            Types\ActivityEdge::class,
            Types\UserEdge::class,
            Types\UserNode::class,
            Types\FeedHighlightsEdge::class,
            Types\FeedHighlightsConnection::class,
            Types\PublisherRecsEdge::class,
            Types\PublisherRecsConnection::class,
            Types\FeedHeaderEdge::class,
            Types\FeedHeaderNode::class,
            Types\FeedExploreTagEdge::class,
            Types\FeedExploreTagNode::class,
        ]));
    }
}
