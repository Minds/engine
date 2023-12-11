<?php
declare(strict_types=1);

namespace Minds\Core\Comments\GraphQL\Types;

use Minds\Core\Comments\Comment;
use Minds\Core\Comments\GraphQL\Types\CommentNode;
use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Session;
use Minds\Entities\User;
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
    public function __construct(
        protected Comment $comment,
        protected string $cursor,
        protected ?User $loggedInUser = null,
    ) {
        $this->loggedInUser ??= Session::getLoggedinUser();
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

    #[Field]
    public function getRepliesCount(): int
    {
        return $this->comment->getRepliesCount();
    }

    #[Field]
    public function hasVotedUp(): bool
    {
        return in_array($this->loggedInUser?->getGuid(), $this->comment->getVotesUp() ?: [], true);
    }

    #[Field]
    public function hasVotedDown(): bool
    {
        return in_array($this->loggedInUser?->getGuid(), $this->comment->getVotesDown() ?: [], true);
    }
}
