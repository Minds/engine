<?php
namespace Minds\Core\Payments\GiftCards\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class GiftCardTransactionEdge implements EdgeInterface
{
    public function __construct(private GiftCardTransaction $giftCardTransaction, private string $cursor = '')
    {
    }

    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    public function getNode(): GiftCardTransaction
    {
        return $this->giftCardTransaction;
    }
}
