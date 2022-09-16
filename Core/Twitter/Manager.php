<?php

namespace Minds\Core\Twitter;

use Minds\Core\Di\Di;

class Manager
{
    public function __construct(
        private ?Repository $repository = null
    ) {
        $this->repository ??= Di::_()->get('Twitter\Repository');
    }

    /**
     * @return string
     */
    public function getRequestOAuthTokenUrl(): string
    {
        return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($this->repository->getRequestOAuthTokenUrlDetails());
    }
}
