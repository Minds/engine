<?php

namespace Minds\Core\Twitter;

use Minds\Core\Di\Di;
use Minds\Core\Twitter\Client\TwitterClient;
use Minds\Core\Twitter\Client\TwitterClientInterface;
use Minds\Core\Twitter\Exceptions\TwitterDetailsNotFoundException;
use Minds\Core\Twitter\Models\TwitterDetails;
use Minds\Entities\User;

class Manager
{
    private User $user;

    public function __construct(
        private ?Repository $repository = null,
        private ?TwitterClientInterface $twitterClient = null
    ) {
        $this->twitterClient ??= new TwitterClient();
        $this->repository ??= Di::_()->get('Twitter\Repository');
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
        ['accessToken' => $accessToken, 'refreshToken' => $refreshToken] =
            $this->twitterClient->generateOAuthAccessToken($authorizationCode);

        $this->repository->storeOAuth2TokenInfo(
            userGuid: $this->user->getGuid(),
            accessToken: $accessToken,
            refreshToken: $refreshToken
        );
    }

    /**
     * @return TwitterDetails
     * @throws TwitterDetailsNotFoundException
     */
    public function getDetails(): TwitterDetails
    {
        return $this->repository->getDetails($this->user->getGuid());
    }
}
