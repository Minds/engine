<?php
declare(strict_types=1);

namespace Minds\Core\Comments\GraphQL\Types;

use Minds\Core\Comments\Comment;
use TheCodingMachine\GraphQLite\Annotations\Type;
use Minds\Core\Feeds\GraphQL\Types\AbstractEntityNode;

/**
 * The CommentNode returns relevant information about the Comment.
 */
#[Type]
class CommentNode extends AbstractEntityNode
{
    public function __construct(
        protected Comment $comment,
    ) {
        $this->entity = $comment;
    }
}
