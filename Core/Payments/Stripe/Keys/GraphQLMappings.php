<?php
namespace Minds\Core\Payments\Stripe\Keys;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\Root\EnumTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;
use TheCodingMachine\GraphQLite\Mappers\StaticTypeMapper;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Payments\Stripe\Keys\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
          Types\StripeKeysType::class,
        ]));
    }
}
