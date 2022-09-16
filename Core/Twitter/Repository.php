<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Minds\Core\Twitter\Client\TwitterClient;
use Minds\Core\Twitter\Client\TwitterClientInterface;

class Repository
{
    public function __construct(
        private ?TwitterClientInterface $twitterClient = null
    ) {
        $this->twitterClient ??= new TwitterClient();
    }

    /**
     * @return array
     */
    public function getRequestOAuthTokenUrlDetails(): array
    {
        return $this->twitterClient->requestOAuthTokenUrlDetails();
    }
}
