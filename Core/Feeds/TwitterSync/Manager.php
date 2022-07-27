<?php
namespace Minds\Core\Feeds\TwitterSync;

use DateTime;
use GuzzleHttp\Exception\ClientException;
use Minds\Controllers\api\v2\admin\pro;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\TwitterSync\Delegates\ChannelLinksDelegate;
use Minds\Core\Feeds\Activity\RichEmbed;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;

class Manager
{
    /**
     * @var string
     * Matches twitter photo url - can be prefixed with a space, and must be at END of text.
     */
    const TWITTER_PHOTO_URL_REGEX = '/\s?https:\/\/twitter\.com\/[\w\d]+\/status\/\d+\/photo\/\d$/';

    /**
     * @var string
     * Matches twitter video url - can be prefixed with a space, and must be at END of text.
     */
    const TWITTER_VIDEO_URL_REGEX = '/\s?https:\/\/twitter\.com\/[\w\d]+\/status\/\d+\/video\/\d$/';

    public function __construct(
        protected Client $client,
        protected Repository $repository,
        protected Config $config,
        protected EntitiesBuilder $entitiesBuilder,
        protected Save $saveAction,
        protected RichEmbed\Manager $richEmbedManager,
        protected ChannelLinksDelegate $channelLinksDelegate,
        protected Logger $logger,
        protected ImageExtractor $imageExtractor
    ) {
    }

    /**
     * Will return a connected account from a Minds User entity
     * @param User $user
     * @return ConnectedAccount
     */
    public function getConnectedAccountByUser(User $user): ConnectedAccount
    {
        return $this->repository->get(userGuid: (string) $user->getGuid());
    }

    /**
     * Will connect a twitter account to a Minds user.
     * @param User $user
     * @param string $twitterUsername
     * @param bool $verify - will check their tweets for minds channel url
     * @return bool
     */
    public function connectAccount(User $user, string $twitterUsername, bool $verify = true): bool
    {
        $twitterUser = $this->getTwitterUserByUsername($twitterUsername);

        $connectedAccount = new ConnectedAccount();
        $connectedAccount->setUserGuid($user->getGuid())
            ->setTwitterUser($twitterUser);

        if ($verify) {
            // Get their latest tweets, does it mention minds.com/:username
            $verificationString = strtolower($this->config->get('site_url') . $user->getUsername());

            // null by default, if there is a match below we set to the id that matched
            // must be within 5 minute window and one of their recent 5 tweets
            $verifiedTweetId = null;

            $latestTweets = $this->getLatestTweets($connectedAccount, limit: 5, gteTimestamp: time() - 300);
            foreach ($latestTweets as $tweet) {
                if (array_filter($tweet->getUrls(), function ($tweetUrl) use ($verificationString) {
                    return strtolower($tweetUrl) === $verificationString;
                })) {
                    $verifiedTweetId = $tweet->getId();
                    break; // Success!
                }
            }

            if (!$verifiedTweetId) {
                throw new UserErrorException("Could not find verification tweet");
            }

            $connectedAccount->setLastImportedTweetId($verifiedTweetId);
        }

        $connectedAccount->setConnectedTimestampSeconds(time());

        $this->channelLinksDelegate->onConnect($connectedAccount);

        return $this->repository->add($connectedAccount);
    }

    /**
     * Disconnect an account
     * @param User $user
     * @return bool
     */
    public function disconnectAccount(User $user): bool
    {
        $connectedAccount = new ConnectedAccount();
        $connectedAccount->setUserGuid($user->getGuid());

        return $this->repository->delete($connectedAccount);
    }

    /**
     * Update an existing account
     * @param ConnectedAccount
     * @return bool
     */
    public function updateAccount(ConnectedAccount $connectedAccount): bool
    {
        return $this->repository->add($connectedAccount);
    }

    /**
     * Returns tweets for a connected account
     * @param ConnectedAccount $connectedAccount
     * @param int $limit - defaults to 24
     * @return iterable<TwitterTweet>
     */
    public function getLatestTweets(ConnectedAccount $connectedAccount, int $limit = 24, int $gteTimestamp = null): iterable
    {
        $queryParams = [
            'tweet.fields' => implode(',', [
                'attachments',
                'in_reply_to_user_id',
                'referenced_tweets',
                'entities', // This is where verify gets the urls from
            ]),
            'max_results' => $limit,
            'exclude' => implode(',', [
                'retweets',
                'replies'
            ]),
            'expansions' => implode(',', [
                'attachments.media_keys'
            ]),
            'media.fields' => implode(',', [
                'type',
                'url',
                'height',
                'width'
            ])
        ];

        // if ($lastImpotedTweetId = $connectedAccount->getLastImportedTweetId()) {
        //     $queryParams['since_id'] = $lastImpotedTweetId;
        // }

        if ($gteTimestamp) {
            $queryParams['start_time'] = date('c', $gteTimestamp);
        }

        $response = $this->client->request('GET', "2/users/{$connectedAccount->getTwitterUser()->getUserId()}/tweets?" . http_build_query($queryParams));

        $json = json_decode($response->getBody()->getContents(), true);

        if (!isset($json['data'])) {
            return null;
        }

        $this->logger->info("[TwitterSync][getLatestTweets()]: {$connectedAccount->getTwitterUser()->getUserId()} returned " . count($json['data']));

        foreach ($json['data'] as $tweetData) {
            if (isset($tweetData['referenced_tweets'])) {
                continue; // Skip as we only want text based tweets, not quotes
            }

            $tweet = new TwitterTweet();
            $tweet
                ->setId($tweetData['id'])
                ->setTwitterUser($connectedAccount->getTwitterUser())
                ->setText($tweetData['text']);

            if (isset($tweetData['entities']) && isset($tweetData['entities']['urls'])) {
                $tweet->setMediaData($this->extractMediaData(
                    $tweetData['attachments']['media_keys'] ?? [],
                    $json['includes']['media'] ?? []
                ));

                foreach ($tweetData['entities']['urls'] as $url) {
                    $expandedUrlText = str_replace($url['url'], $url['expanded_url'], $tweet->getText());
                    
                    // if we have extracted media, strip the media url from the post.
                    if (count($tweet->getMediaData())) {
                        $originalExpandedUrlText = $expandedUrlText;
                        $expandedUrlText = $this->stripPhotoUrl($expandedUrlText);
                        // if no photo url detected, try strip video URL.
                        if ($expandedUrlText === $originalExpandedUrlText) {
                            $expandedUrlText = $this->stripVideoUrl($expandedUrlText);
                        }
                    }

                    $tweet->setText($expandedUrlText);
                }

                $tweet->setUrls(array_map(function ($url) {
                    return $url['expanded_url'];
                }, $tweetData['entities']['urls']));
            }

            yield $tweet;
        }
    }

    /**
     * Sync the tweets
     * Run at regular intervals
     */
    public function syncTweets(): iterable
    {
        foreach ($this->repository->getList() as $connectedAccount) {
            if ($connectedAccount->getTwitterUser()->getFollowersCount() < ($this->config->get('twitter')['min_followers_for_sync'] ?? 25000)) {
                continue; // Too few followers
            }

            $i = 0;
            $recentTweets = $this->getLatestTweets($connectedAccount, gteTimestamp: $connectedAccount->getLastSyncUnixTs() ?: time());
            foreach ($recentTweets as $recentTweet) {
                $owner = $this->entitiesBuilder->single($connectedAccount->getUserGuid());
                if (!$owner) {
                    continue;
                }
                $activity = new Activity();
                // Ouch! There needs to be a cleaner way to build these entities
                $activity->container_guid = $owner->guid;
                $activity->owner_guid = $owner->guid;
                $activity->ownerObj = $owner->export();
                $activity->setMessage($recentTweet->getText());

                $photos = $recentTweet->getPhotosData();

                if (count($photos)) {
                    // Only extract for a first bit of attached media for now
                    // in future we can add multi-image support.
                    $this->imageExtractor->extractAndUploadToActivity(
                        $photos[0],
                        $activity
                    );
                }

                if ($recentTweet->getUrls() && isset($recentTweet->getUrls()[0]) && !count($photos)) {
                    $url = $recentTweet->getUrls()[0];
                    try {
                        $richEmbed = $this->richEmbedManager->getRichEmbed($url);
                        $activity
                            ->setTitle($richEmbed['meta']['title'])
                            ->setBlurb($richEmbed['meta']['description'])
                            ->setURL($url)
                            ->setThumbnail($richEmbed['links']['thumbnail'][0]['href']);
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                        continue;
                    }
                }

                try {
                    $this->saveAction->setEntity($activity)->save();
                } catch (\Exception $e) {
                    // sadly we have just a generic exception for found spam
                }

                // Update our last imported tweet, but only the first one
                if (++$i === 1) {
                    $connectedAccount->setLastImportedTweetId($recentTweet->getId());
                }
            }

            $connectedAccount->setLastSyncUnixTs(time());
            $this->updateAccount($connectedAccount);

            yield $connectedAccount;
        }
    }

    /**
     * Return a TwitterUser entity from username
     * @param string $username
     * @return TwitterUser
     */
    protected function getTwitterUserByUsername($username): TwitterUser
    {
        $queryParams = [
            'user.fields' => implode(',', [
                'created_at',
                'description',
                'entities',
                'id',
                'location',
                'name',
                'profile_image_url',
                'protected',
                'public_metrics',
                'url',
                'username',
                'verified',
                'withheld',
            ]),
        ];

        try {
            $response = $this->client->request('GET', "2/users/by/username/$username?" . http_build_query($queryParams));
        } catch (ClientException $e) {
            throw new NotFoundException(); // Probably not ok to assume...
        }

        $json = json_decode($response->getBody()->getContents(), true);

        $twitterUser = new TwitterUser();
        $twitterUser->setUserId((string) $json['data']['id'])
            ->setUsername((string) $json['data']['username'])
            ->setFollowersCount($json['data']['public_metrics']['followers_count']);

        return $twitterUser;
    }

    /**
     * Strips photo URL from end of tweet text - useful because for photos and videos, Twitter appends
     * a link to the end of the text that is not visible on-site.
     * @param string $text - text to strip.
     * @return string - text without twitter photo link at end.
     */
    protected function stripPhotoUrl(string $text): string
    {
        return preg_replace(self::TWITTER_PHOTO_URL_REGEX, '', $text, 1);
    }

    /**
     * Strips video URL from end of tweet text - useful because for photos and videos, Twitter appends
     * a link to the end of the text that is not visible on-site.
     * @param string $text - text to strip.
     * @return string - text without twitter video link at end.
     */
    protected function stripVideoUrl(string $text): string
    {
        return preg_replace(self::TWITTER_VIDEO_URL_REGEX, '', $text, 1);
    }

    /**
     * Extracts media data such as photo URLs and dimensions from tweet.
     * @param array $mediaKeys - the media keys attached to the post.
     * @param array $includes - the extended media object returned from twitter API,
     * should contain matching media keys.
     * @return array - MediaData[] array of MediaData items for a given post.
     */
    protected function extractMediaData(array $mediaKeys, array $includes): array
    {
        if (!count($mediaKeys) || !count($includes)) {
            return [];
        }

        $mediaDataArray = [];

        foreach ($mediaKeys as $mediaKey) {
            // get connected expanded media object.
            $extendedMediaObjectArray = array_values(array_filter(
                $includes,
                function ($media) use ($mediaKey) {
                    return $media['media_key'] === $mediaKey;
                }
            ));

            // if no extended media object found, skip.
            if (!$extendedMediaObjectArray || !$extendedMediaObjectArray[0]) {
                break;
            }

            $extendedMediaObject = $extendedMediaObjectArray[0];

            // if extended media object type is photo, get url and push to $urls array.
            if ($extendedMediaObject['type'] === 'photo' || $extendedMediaObject['type'] === 'video') {
                // videos do not currently export a URL from the V2 API.
                $mediaDataItem = (new MediaData())
                    ->setType($extendedMediaObject['type'])
                    ->setUrl($extendedMediaObject['url'] ?? null)
                    ->setHeight($extendedMediaObject['height'] ?? null)
                    ->setWidth($extendedMediaObject['width']  ?? null);
                array_push($mediaDataArray, $mediaDataItem);
            }
        }

        return $mediaDataArray;
    }
}
