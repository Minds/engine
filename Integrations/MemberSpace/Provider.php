<?php
declare(strict_types=1);

namespace Minds\Integrations\MemberSpace;

use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            Events::class,
            fn (Di $di): Events => new Events(
                eventsDispatcher: $di->get('EventsDispatcher'),
                config: $di->get(Config::class),
            )
        );

        $this->di->bind(
            MemberSpaceService::class,
            fn (Di $di): MemberSpaceService => new MemberSpaceService(
                httpClient: $di->get(Client::class),
            )
        );
    }
}
