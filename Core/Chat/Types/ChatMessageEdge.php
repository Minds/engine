<?php
namespace Minds\Core\Chat\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class ChatMessageEdge implements EdgeInterface
{
    public function __construct(
        protected ChatMessageNode $node,
        protected string $cursor = ''
    ) {
        
    }

    #[Field]
    public function getId(): ID
    {
        return $this->node->getId();
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
