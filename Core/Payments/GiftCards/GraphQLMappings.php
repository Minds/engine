<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Payments\GiftCards\Controllers');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            GiftCard::class
        ]));
    }
}
