<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\Payments\V2\Models\PaymentMethod;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Payments\V2\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Payments\V2');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            PaymentMethod::class
        ]));
    }
}
