<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * The UserEdge contains the UserNode (entity) and the cursor (for pagination).
 * Further information can be provided here, such as relationships or other contexts.
 */
#[Type]
class UserEdge implements EdgeInterface
{
    public function __construct(protected User $user, protected string $cursor)
    {
        $this->user = $user;
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("user-" . $this->user->getGuid());
    }

    #[Field]
    public function getType(): string
    {
        return "user";
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    public function getNode(): UserNode
    {
        return new UserNode($this->user);
    }
}
