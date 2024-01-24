<?php

declare(strict_types=1);

namespace Minds\Core\Webfinger;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(Controller::class, function ($di) {
            return new Controller(
                entitiesBuilder: $di->get('EntitiesBuilder'),
                config: $di->get('Config'),
            );
        });
        $this->di->bind(Client::class, function ($di) {
            return new Client(
                httpClient: new \GuzzleHttp\Client(),
                config: $di->get('Config')
            );
        });
        $this->di->bind(WebfingerService::class, function ($di) {
            return new WebfingerService($di->get(Client::class));
        });
    }
}
