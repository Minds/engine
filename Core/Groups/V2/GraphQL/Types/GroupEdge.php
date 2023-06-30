<?php
namespace Minds\Core\Groups\V2\GraphQL\Types;

use Minds\Entities\Group;
use Minds\Core\GraphQL\Types\EdgeInterface;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * The GroupEdge holds the GroupNode and cursor information.
 * Other relevant information can also be included in the Edge.
 */
#[Type]
class GroupEdge implements EdgeInterface
{
    public function __construct(protected Group $group, protected string $cursor)
    {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("group-" . $this->group->getGuid());
    }

    #[Field]
    public function getType(): string
    {
        return "group";
    }

    #[Field]
    public function getNode(): GroupNode
    {
        return new GroupNode($this->group);
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }
}
