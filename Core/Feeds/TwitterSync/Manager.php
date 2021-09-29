<?php
namespace Minds\Core\Feeds\TwitterSync;

use DateTime;
use GuzzleHttp\Exception\ClientException;
use Minds\Controllers\api\v2\admin\pro;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\TwitterSync\Delegates\ChannelLinksDelegate;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Minds\Helpers\Text;

class Manager
{
    public function __construct(
        protected Client $client,
        protected Repository $repository,
        protected Config $config,
        protected EntitiesBuilder $entitiesBuilder,
        protected Save $saveAction,
        protected ChannelLinksDelegate $channelLinksDelegate,
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
            'media.fields' => 'url',
            'max_results' => $limit,
            'exclude' => implode(',', [
                'retweets',
                'replies'
            ]),
        ];

        if ($lastImpotedTweetId = $connectedAccount->getLastImportedTweetId()) {
            $queryParams['since_id'] = $lastImpotedTweetId;
        }

        if ($gteTimestamp) {
            $queryParams['start_time'] = date('c', $gteTimestamp);
        }

        $response = $this->client->request('GET', "2/users/{$connectedAccount->getTwitterUser()->getUserId()}/tweets?" . http_build_query($queryParams));

        $json = json_decode($response->getBody()->getContents(), true);

        if (!isset($json['data'])) {
            return null;
        }

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
                foreach ($tweetData['entities']['urls'] as $url) {
                    $tweet->setText(str_replace($url['url'], $url['expanded_url'], $tweet->getText()));
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
     * Run at regular intervald
     *
     */
    public function syncTweets(): iterable
    {
        foreach ($this->repository->getList() as $connectedAccount) {
            if ($connectedAccount->getTwitterUser()->getFollowersCount() < ($this->config->get('twitter')['min_followers_for_sync'] ?? 25000)) {
                continue; // Too few followers
            }

            $i = 0;
            $recentTweets = $this->getLatestTweets($connectedAccount);
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
                //
                $activity->setMessage($recentTweet->getText());
                $this->saveAction->setEntity($activity)->save();

                // Update our last imported tweet, but only the first one
                if (++$i === 1) {
                    $connectedAccount->setLastImportedTweetId($recentTweet->getId());
                    $this->updateAccount($connectedAccount);
                }
            }

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
}
