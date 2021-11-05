<?php
namespace Minds\Core\Feeds\TwitterSync;

use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setTwitterUser(TwitterUser $TwitterUser)
 * @method TwitterUser getTwitterUser()
 * @method self setLastImportedTweetId(string $lastImportedTweetId)
 * @method string getLastImportedTweetId()
 * @method self setLastSyncUnixTs(int $lastSyncUnixTs)
 * @method int getLastSyncUnixTs()
 * @method self setDiscoverable(bool $discoverable)
 * @method bool isDiscoverable()
 * @method self setConnectedTimestampSeconds(int $seconds)
 * @method int getConnectedTimestampSeconds()
 */
class ConnectedAccount implements ExportableInterface
{
    use MagicAttributes;

    /** @var string */
    protected $userGuid;

    /** @var TwitterUser */
    protected $twitterUser;

    /** @var string */
    protected $lastImportedTweetId = "0";

    /** @var int */
    protected $lastSyncUnixTs = 0;

    /** @var bool */
    protected $discoverable = true;

    /** @var int */
    protected $connectedTimestampSeconds;

    /**
     * @return array
     */
    public function export(array $extras = []): array
    {
        return[
            'user_guid' => (string) $this->userGuid,
            'twitter_username' => $this->twitterUser->getUsername(),
            'discoverable' => $this->discoverable,
        ];
    }
}
