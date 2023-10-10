<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace("Minds\Core\Payments\Lago\Controllers");
        $this->schemaFactory->addTypeNamespace("Minds\\Core\\Payments\\Lago\\Enums");
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([

        ]));
    }
}
