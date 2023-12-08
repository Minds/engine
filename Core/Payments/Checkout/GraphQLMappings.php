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
        $this->schemaFactory->addControllerNamespace('Minds\Core\Payments\Checkout\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Payments\Checkout\Enums');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Payments\Checkout\Types');

        $this->schemaFactory->addTypeNamespace('Minds\Core\Payments\Checkout\Types\Factories');
    }
}
