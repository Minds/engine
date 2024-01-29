<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs;

use Minds\Core\GraphQL\AbstractGraphQLMappings;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\MultiTenant\MobileConfigs\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\Core\MultiTenant\MobileConfigs\Enums');
        $this->schemaFactory->addTypeNamespace('Minds\Core\MultiTenant\MobileConfigs\Types');
    }
}
