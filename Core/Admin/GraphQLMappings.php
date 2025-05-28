<?php
declare(strict_types=1);

namespace Minds\Core\Admin;

use Minds\Core\GraphQL\AbstractGraphQLMappings;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\Admin\Controllers');
        $this->schemaFactory->addNamespace('Minds\\Core\\Admin\\Types\\HashtagExclusion');
    }
}
