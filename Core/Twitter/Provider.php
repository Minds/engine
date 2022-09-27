<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind("Twitter\Controller", function ($di) {
            return new Controller();
        });
        $this->di->bind("Twitter\Manager", function ($di) {
            return new Manager();
        });
        $this->di->bind("Twitter\Repository", function ($di) {
            return new Repository();
        });
    }
}
