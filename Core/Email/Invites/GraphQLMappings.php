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
        $this->schemaFactory->addNamespace('Minds\Core\Email\Invites\Controllers');
        $this->schemaFactory->addNamespace('Minds\Core\Email\Invites\Types');
        $this->schemaFactory->addNamespace('Minds\Core\Email\Invites\Enums');
    }
}
