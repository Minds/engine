<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\PostSubscriptions;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Notifications\PostSubscriptions\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\\Core\\Notifications\\PostSubscriptions\\Enums');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            PostSubscription::class,
        ]));
    }
}
