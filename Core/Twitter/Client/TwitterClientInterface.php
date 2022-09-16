<?php

namespace Minds\Core\Twitter\Client;

/**
 *
 */
interface TwitterClientInterface
{
    public function requestOAuthTokenUrlDetails(): array;
}
