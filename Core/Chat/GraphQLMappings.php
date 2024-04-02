<?php
declare(strict_types=1);

namespace Minds\Core\Chat;

use Minds\Core\GraphQL\AbstractGraphQLMappings;

class GraphQLMappings extends AbstractGraphQLMappings
{
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Chat\\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\\Core\\Chat\\Enums');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Chat\Types');
    }
}
