<?php

declare(strict_types=1);

namespace Minds\Core\Twitter\Models;

use Minds\Common\Access;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Entities\EntityInterface;
use Minds\Entities\ExportableInterface;
use Minds\Helpers\OpenSSL;
use Minds\Traits\MagicAttributes;

/**
 * Represents the twitter details stored in Cassandra within the twitter_sync table
 *
 * @method self setUserGuid(string $userGuid)
 * @method string getUserGuid()
 * @method self setDiscoverable(bool $discoverable)
 * @method bool getDiscoverable()
 * @method self setLastImportedTweetId(int $lastImportedTweetId)
 * @method int|null getLastImportedTweetId()
 * @method self setLastSyncTimestamp(int $lastSyncTimestamp)
 * @method int|null getLastSyncTimestamp()
 * @method self setTwitterFollowersCount(int $twitterFollowersCount)
 * @method int|null getTwitterFollowersCount()
 * @method self setTwitterUserId(int $twitterUserId)
 * @method int|null getTwitterUserId()
 * @method self setTwitterUsername(string $twitterUsername)
 * @method string|null getTwitterUsername()
 * @method self setAccessTokenExpiry(int $accessTokenExpiry)
 * @method int|null getAccessTokenExpiry()
 */
class TwitterDetails implements ExportableInterface, EntityInterface
{
    use MagicAttributes;

    private string $userGuid;
    private bool $discoverable = false;
    private ?int $lastImportedTweetId = null;
    private ?int $lastSyncTimestamp = null;
    private ?int $twitterFollowersCount = null;
    private ?int $twitterUserId = null;
    private ?string $twitterUsername = null;
    private ?string $accessToken = null;
    private ?int $accessTokenExpiry = null;
    private ?string $refreshToken = null;

    public function __construct(
        private ?MindsConfig $mindsConfig = null
    ) {
        $this->mindsConfig ??= Di::_()->get("Config");
    }

    public static function fromData(array $data): self
    {
        $twitterDetails = new self;

        if (isset($data['user_guid'])) {
            $twitterDetails->setUserGuid($data['user_guid']);
        }

        if (isset($data['discoverable'])) {
            $twitterDetails->setDiscoverable($data['discoverable']);
        }

        if (isset($data['last_imported_tweet_id'])) {
            $twitterDetails->setLastImportedTweetId($data['last_imported_tweet_id']);
        }

        if (isset($data['last_sync_ts'])) {
            $twitterDetails->setLastSyncTimestamp($data['last_sync_ts']);
        }

        if (isset($data['twitter_followers_count'])) {
            $twitterDetails->setTwitterFollowersCount($data['twitter_followers_count']);
        }

        if (isset($data['twitter_user_id'])) {
            $twitterDetails->setTwitterUserId($data['twitter_user_id']);
        }

        if (isset($data['twitter_username'])) {
            $twitterDetails->setTwitterUsername($data['twitter_username']);
        }

        if (isset($data['access_token'])) {
            $twitterDetails->setAccessToken($data['access_token']);
        }

        if (isset($data['access_token_expiry'])) {
            $twitterDetails->setAccessTokenExpiry((int) $data['access_token_expiry']);
        }

        if (isset($data['refresh_token'])) {
            $twitterDetails->setRefreshToken($data['refresh_token']);
        }

        return $twitterDetails;
    }

    /**
     * @inheritDoc
     */
    public function getGuid(): ?string
    {
        return $this->userGuid;
    }

    /**
     * @inheritDoc
     */
    public function getOwnerGuid(): ?string
    {
        return $this->userGuid;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'twitter-details';
    }

    /**
     * @inheritDoc
     */
    public function getSubtype(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getUrn(): string
    {
        return 'urn:' . $this->getType() . ':' . $this->getGuid();
    }

    /**
     * @inheritDoc
     */
    public function getAccessId(): string
    {
        return (string) Access::PUBLIC;
    }

    public function setAccessToken(string $accessToken): self
    {
        if (OpenSSL::decrypt($accessToken, file_get_contents($this->mindsConfig->get('encryptionKeys')['twt_tokens']['private'])) !== null) {
            $this->accessToken = $accessToken;
            return $this;
        }

        $this->accessToken = base64_encode(OpenSSL::encrypt($accessToken, file_get_contents($this->mindsConfig->get('encryptionKeys')['twt_tokens']['public'])));
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken ? OpenSSL::decrypt(base64_decode($this->accessToken, true), file_get_contents($this->mindsConfig->get('encryptionKeys')['twt_tokens']['private'])) : null;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        if (OpenSSL::decrypt($refreshToken, file_get_contents($this->mindsConfig->get('encryptionKeys')['twt_tokens']['private'])) !== null) {
            $this->refreshToken = $refreshToken;
            return $this;
        }
        $this->refreshToken = base64_encode(OpenSSL::encrypt($refreshToken, file_get_contents($this->mindsConfig->get('encryptionKeys')['twt_tokens']['public'])));
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken ? OpenSSL::decrypt(base64_decode($this->refreshToken, true), file_get_contents($this->mindsConfig->get('encryptionKeys')['twt_tokens']['private'])) : null;
    }

    /**
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'user_guid' => $this->getUserGuid(),
            'discoverable' => $this->getDiscoverable(),
            'last_imported_tweet_id' => $this->getLastImportedTweetId(),
            'last_sync_ts' => $this->getLastSyncTimestamp(),
            'twitter_followers_count' => $this->getTwitterFollowersCount(),
            'twitter_user_id' => $this->getTwitterUserId(),
            'twitter_username' => $this->getTwitterUsername(),
            'twitter_oauth2_connected' => !empty($this->accessToken)
        ];
    }
}
