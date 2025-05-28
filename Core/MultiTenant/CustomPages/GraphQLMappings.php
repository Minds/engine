<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\CustomPages;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addNamespace('Minds\Core\MultiTenant\CustomPages\Controllers');
        $this->schemaFactory->addNamespace('Minds\Core\MultiTenant\CustomPages\Enums');
        $this->schemaFactory->addNamespace('Minds\Core\MultiTenant\CustomPages\Types');

        $this->schemaFactory->setInputTypeValidator(new Validators\CustomPageInputValidator());
    }
}
