<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class InviteEdge implements EdgeInterface
{
    public function __construct(
        private readonly Invite  $node,
        private readonly ?string $cursor = null
    ) {
    }

    #[Field] public function getNode(): ?Invite
    {
        return $this->node;
    }

    #[Field] public function getCursor(): string
    {
        return $this->cursor ?? "";
    }
}
