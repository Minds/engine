<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards;

use Minds\Core\GraphQL\AbstractGraphQLMappings;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;

class GraphQLMappings extends AbstractGraphQLMappings
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->schemaFactory->addControllerNamespace('Minds\Core\Payments\GiftCards\Controllers');
        $this->schemaFactory->addTypeNamespace('Minds\\Core\\Payments\\GiftCards\\Enums');
        $this->schemaFactory->addTypeMapperFactory(new StaticClassListTypeMapperFactory([
            GiftCard::class,
            GiftCardTransaction::class,
            Types\GiftCardsConnection::class,
            Types\GiftCardEdge::class,
            Types\GiftCardBalanceByProductId::class,
            Types\GiftCardTransactionsConnection::class,
            Types\GiftCardTransactionEdge::class,
            Types\GiftCardTarget::class,
        ]));

        $this->schemaFactory->setInputTypeValidator(new Validators\GiftCardTargetInputValidator());
    }
}
