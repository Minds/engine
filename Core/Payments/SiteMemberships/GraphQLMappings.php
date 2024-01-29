<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships;

use Minds\Core\GraphQL\AbstractGraphQLMappings;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\SiteMemberships\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\Core\SiteMemberships\Enums');
        $this->schemaFactory->addTypeNamespace('Minds\Core\SiteMemberships\Types');
    }
}
