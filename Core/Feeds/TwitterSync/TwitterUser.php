<?php
namespace Minds\Core\Feeds\TwitterSync;

use Minds\Traits\MagicAttributes;

/**
 * @method self setUserId(string $userId)
 * @method string getUserId()
 * @method self setUsername(string $username)
 * @method string getUsername()
 * @method self setFollowersCount(int $followersCount)
 * @method int getFollowersCount()
 */
class TwitterUser
{
    use MagicAttributes;

    /** @var string */
    protected $userId;

    /** @var string */
    protected $username;

    /** @var int */
    protected $followersCount = 0;
}
