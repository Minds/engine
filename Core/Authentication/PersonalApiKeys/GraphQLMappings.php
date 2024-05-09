<?php
declare(strict_types=1);

namespace Minds\Core\Authentication\PersonalApiKeys;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\Router\Enums\ApiScopeEnum;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Authentication\PersonalApiKeys\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
           PersonalApiKey::class,
           ApiScopeEnum::class,
        ]));
    }
}
