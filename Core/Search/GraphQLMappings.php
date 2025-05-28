<?php
namespace Minds\Core\Search;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\Root\EnumTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;
use TheCodingMachine\GraphQLite\Mappers\StaticTypeMapper;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Search\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
          Types\SearchResultsConnection::class,
          Types\SearchResultsCount::class,
        ]));
        $this->schemaFactory->addNamespace("Minds\\Core\\Search\\Enums");
    }
}
