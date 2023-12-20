<?php
declare(strict_types=1);

namespace Minds\Core\Comments\EmbeddedComments;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\\Core\\Comments\\EmbeddedComments\\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\EmbeddedCommentsConnection::class,
            Models\EmbeddedCommentsSettings::class,
        ]));
    }
}
