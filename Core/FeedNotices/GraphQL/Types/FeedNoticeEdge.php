<?php
namespace Minds\Core\FeedNotices\GraphQL\Types;

use Minds\Core\FeedNotices\Notices\AbstractNotice;
use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * The FeedNoticeEdge hold the FeedNoticeNode and cursor information.
 * Other relevant information, such as relationships, can also be included in the Edge.
 */
#[Type]
class FeedNoticeEdge implements EdgeInterface
{
    public function __construct(protected AbstractNotice $feedNotice, protected string $cursor)
    {
    }

    #[Field]
    public function getType(): string
    {
        return "feed-notice";
    }

    #[Field]
    public function getNode(): FeedNoticeNode
    {
        return new FeedNoticeNode($this->feedNotice);
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }
}
