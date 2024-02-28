<?php
namespace Minds\Core\Chat\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class ChatMessageEdge implements EdgeInterface
{
    public function __construct(
        protected ChatMessageNode $node,
        protected string $cursor = ''
    ) {
        
    }

    #[Field]
    public function getNode(): ChatMessageNode
    {
        return $this->node;
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }
}
