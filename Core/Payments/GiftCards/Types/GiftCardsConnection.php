<?php
namespace Minds\Core\Payments\GiftCards\Types;

use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

#[Type]
class GiftCardsConnection extends Connection
{
    /**
     * @return GiftCardEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
