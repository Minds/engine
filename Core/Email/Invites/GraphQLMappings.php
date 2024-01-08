<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites;

use Minds\Core\GraphQL\AbstractGraphQLMappings;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Email\Invites\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Email\Invites\Types');
        $this->schemaFactory->addTypeNamespace('Minds\Core\Email\Invites\Enums');
    }
}
