<?php

declare(strict_types=1);

namespace Minds\Core\Twitter\Client\DTOs;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setExcludeReplyUserIds(array $excludeReplyUserIds)
 * @method array getExcludeReplyUserIds()
 * @method self setInReplyToTweetId(string $inReplyToTweetId)
 * @method string getInReplyToTweetId()
 */
class Reply implements ExportableInterface
{
    use MagicAttributes;

    private array $excludeReplyUserIds;
    private string $inReplyToTweetId;

    public function export(array $extras = []): array
    {
        return [
            'exclude_reply_user_ids' => $this->getExcludeReplyUserIds(),
            'in_reply_to_tweet_id' => $this->getInReplyToTweetId()
        ];
    }
}
