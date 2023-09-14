<?php
namespace Minds\Core\FeedNotices\GraphQL\Types;

use Minds\Core\FeedNotices\Notices\AbstractNotice;
use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * The FeedNoticeNode returns relevant information about the FeedNotice.
 * This should be refactored in the future to allow the backend to construct the entire
 * notice. Currently, the frontend builds out the components and routes by the key.
 */
#[Type]
class FeedNoticeNode implements NodeInterface
{
    public function __construct(private AbstractNotice $feedNotice)
    {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID('feed-notice-' . $this->feedNotice->getKey());
    }

    #[Field(description: 'The location in the feed this notice should be displayed. top or inline.')]
    public function getLocation(): string
    {
        return $this->feedNotice->getLocation();
    }

    #[Field(description: 'The key of the notice that the client should render')]
    public function getKey(): string
    {
        return $this->feedNotice->getKey();
    }

    #[Field(description: 'Whether the notice is dismissible')]
    public function isDismissible(): bool
    {
        return $this->feedNotice->isDismissible();
    }
}
