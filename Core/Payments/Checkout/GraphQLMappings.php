<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout;

use Minds\Core\GraphQL\AbstractGraphQLMappings;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Payments\Checkout\Controllers');
        $this->schemaFactory->addNamespace('Minds\Core\Payments\Checkout\Enums');
        $this->schemaFactory->addNamespace('Minds\Core\Payments\Checkout\Types');

        $this->schemaFactory->addNamespace('Minds\Core\Payments\Checkout\Types\Factories');
    }
}
