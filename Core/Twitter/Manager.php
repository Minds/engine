<?php

namespace Minds\Core\Twitter;

use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Twitter\Client\DTOs\TweetDTO;
use Minds\Core\Twitter\Client\TwitterClient;
use Minds\Core\Twitter\Client\TwitterClientInterface;
use Minds\Core\Twitter\Exceptions\TwitterDetailsNotFoundException;
use Minds\Core\Twitter\Models\TwitterDetails;
use Minds\Entities\User;
use Psr\SimpleCache\InvalidArgumentException;

class Manager
{
    private User $user;
    private const TWITTER_REDIRECT_PATH_KEY_PREFIX = "twitter_oauth_redirect_path_";
    private const TWITTER_REDIRECT_PATH_KEY_TTL = 30; // seconds

    public function __construct(
        private ?Repository $repository = null,
        private ?TwitterClientInterface $twitterClient = null,
        private ?PsrWrapper $cache = null,
        private ?MindsConfig $mindsConfig = null
    ) {
        $this->twitterClient ??= new TwitterClient();
        $this->repository ??= Di::_()->get('Twitter\Repository');
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
        $this->mindsConfig ??= Di::_()->get('Config');
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestOAuthAuthorizationCodeUrl(): string
    {
        return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($this->twitterClient->requestOAuthAuthorizationCodeUrlDetails(), "", null, PHP_QUERY_RFC3986);
    }

    /**
     * @param string $authorizationCode
     * @return void
     */
    public function generateOAuthAccessToken(string $authorizationCode): void
    {
        ['accessToken' => $accessToken, 'refreshToken' => $refreshToken, 'accessTokenExpiry' => $accessTokenExpiry] =
            $this->twitterClient->generateOAuthAccessToken($authorizationCode);

        $this->repository->storeOAuth2TokenInfo(
            userGuid: $this->user->getGuid(),
            accessToken: $accessToken,
            accessTokenExpiry: $accessTokenExpiry,
            refreshToken: $refreshToken
        );
    }

    /**
     * Publish Tweet on user's Twitter account
     * @param string $text
     * @return bool
     * @throws TwitterDetailsNotFoundException
     */
    public function postTweet(string $text): bool
    {
        $twitterDetails = $this->repository->getDetails($this->user->getGuid());

        $tweet = (new TweetDTO())
            ->setText($text);

        $accessToken = $this->checkAndRefreshToken($twitterDetails);

        return $this->twitterClient->postTweet($tweet, $accessToken);
    }

    /**
     * @param TwitterDetails $twitterDetails
     * @return string
     */
    private function checkAndRefreshToken(TwitterDetails $twitterDetails): string
    {
        $accessToken = $twitterDetails->getAccessToken();
        if (time() >= $twitterDetails->getAccessTokenExpiry()) {
            ['accessToken' => $accessToken, 'refreshToken' => $refreshToken, 'accessTokenExpiry' => $accessTokenExpiry] =
                $this->twitterClient->refreshOAuthAccessToken($twitterDetails->getRefreshToken());

            $this->repository->storeOAuth2TokenInfo(
                userGuid: $this->user->getGuid(),
                accessToken: $accessToken,
                accessTokenExpiry: $accessTokenExpiry,
                refreshToken: $refreshToken
            );
        }

        return $accessToken;
    }

    /**
     * @return TwitterDetails
     */
    public function getDetails(): TwitterDetails
    {
        try {
            $response = $this->repository->getDetails($this->user->getGuid());
        } catch (TwitterDetailsNotFoundException $e) {
            $response = (new TwitterDetails())->setUserGuid($this->user->getGuid());
        }

        return $response;
    }

    /**
     * @param string $path
     * @return void
     * @throws InvalidArgumentException
     */
    public function storeOAuthRedirectPath(string $path): void
    {
        $this->cache->set(
            self::TWITTER_REDIRECT_PATH_KEY_PREFIX . $this->user->getGuid(),
            $path,
            self::TWITTER_REDIRECT_PATH_KEY_TTL
        );
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function getStoredOAuthRedirectPath(): string
    {
        return rtrim($this->mindsConfig->get('site_url'), '/') . $this->cache->get(self::TWITTER_REDIRECT_PATH_KEY_PREFIX . $this->user->getGuid());
    }
}
