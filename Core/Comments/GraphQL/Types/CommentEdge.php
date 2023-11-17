<?php
declare(strict_types=1);

namespace Minds\Core\Comments\GraphQL\Types;

use Minds\Core\Comments\Comment;
use Minds\Core\Comments\GraphQL\Types\CommentNode;
use Minds\Core\GraphQL\Types\EdgeInterface;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * The CommentEdge holds the CommentNode and cursor information.
 * Other relevant information can also be included in the Edge.
 */
#[Type]
class CommentEdge implements EdgeInterface
{
    public function __construct(protected Comment $comment, protected string $cursor)
    {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("comment-" . $this->comment->getGuid());
    }

    #[Field]
    public function getType(): string
    {
        return "comment";
    }

    #[Field]
    public function getNode(): CommentNode
    {
        return new CommentNode($this->comment);
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }
}
