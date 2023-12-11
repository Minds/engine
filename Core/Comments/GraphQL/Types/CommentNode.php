<?php
declare(strict_types=1);

namespace Minds\Core\Comments\GraphQL\Types;

use Minds\Core\Comments\Comment;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use TheCodingMachine\GraphQLite\Annotations\Type;
use Minds\Core\Feeds\GraphQL\Types\AbstractEntityNode;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * The CommentNode returns relevant information about the Comment.
 */
#[Type]
class CommentNode extends AbstractEntityNode
{
    public function __construct(
        protected Comment $comment,
        private ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        $this->entity = $comment;
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }

    #[Field]
    public function getOwner(): UserNode
    {
        /** @var User */
        $owner = $this->entitiesBuilder->single($this->comment->getOwnerGuid());
        return new UserNode($owner);
    }

    #[Field]
    public function getParentPath(): string
    {
        return $this->comment->getParentPath();
    }

    #[Field]
    public function getChildPath(): string
    {
        return $this->comment->getChildPath();
    }

    #[Field]
    public function getBody(): string
    {
        return $this->comment->getBody();
    }

}
