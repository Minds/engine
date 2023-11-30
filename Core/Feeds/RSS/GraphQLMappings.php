<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS;

use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Feeds\RSS\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Feeds\RSS\Enums');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Feeds\RSS\Types\Factories');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            RssFeed::class
        ]));
    }
}
