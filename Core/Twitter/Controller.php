<?php

namespace Minds\Core\Twitter;

use Minds\Core\Di\Di;
use Psr\Http\Message\ServerRequestInterface;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Twitter\Manager');
    }

    public function requestTwitterOAuthToken(ServerRequestInterface $request)
    {
    }
}
