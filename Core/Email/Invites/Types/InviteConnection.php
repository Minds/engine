<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Types;

use Minds\Core\GraphQL\Types\Connection;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class InviteConnection extends Connection implements NodeInterface
{
    /**
     * @var InviteEdge[]
     */
    protected array $edges = [];


    #[Field] public function getId(): ID
    {
        return new ID("invite-connection-" . Guid::build());
    }

    /**
     * @return InviteEdge[]
     */
    #[Field] public function getEdges(): array
    {
        return $this->edges;
    }
}
