<?php
namespace Minds\Core\Payments\GiftCards\Types;

use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class GiftCardTransactionsConnection extends Connection
{
    /**
     * @return GiftCardTransactionEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
