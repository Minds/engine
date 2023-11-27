<?php
declare(strict_types=1);

namespace Minds\Core\Authentication\Oidc;

use Minds\Core\Authentication\Oidc\GqlTypes\OidcProviderPublic;
use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Authentication\Oidc\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            OidcProviderPublic::class,
        ]));
    }
}
