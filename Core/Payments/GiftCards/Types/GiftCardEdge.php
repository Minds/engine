<?php
namespace Minds\Core\Payments\GiftCards\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class GiftCardEdge implements EdgeInterface
{
    public function __construct(private GiftCard $giftCard, private string $cursor = '')
    {
    }

    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    public function getNode(): GiftCard
    {
        return $this->giftCard;
    }
}
