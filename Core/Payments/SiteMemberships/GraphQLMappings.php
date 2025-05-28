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
        $this->schemaFactory->addNamespace('Minds\Core\Payments\SiteMemberships\Controllers');
        $this->schemaFactory->addNamespace('Minds\Core\Payments\SiteMemberships\Enums');
        $this->schemaFactory->addNamespace('Minds\Core\Payments\SiteMemberships\Types');
        $this->schemaFactory->addNamespace('Minds\Core\Payments\SiteMemberships\Types\Factories');
    }
}
