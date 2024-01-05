<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Storage\Quotas\Controllers');
        $this->schemaFactory->addControllerNamespace('Minds\\Core\\Storage\\Quotas\\Enums');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            Types\QuotaDetails::class,
            Types\AssetConnection::class,
        ]));
    }
}
