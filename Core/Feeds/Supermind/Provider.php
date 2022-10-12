<?php

declare(strict_types=1);

namespace Minds\Core\Feeds\Supermind;

use Minds\Core\Di\ImmutableException;

class Provider extends \Minds\Core\Di\Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            'Feeds\Superminds\Controller',
            function ($di): Controller {
                return new Controller();
            }
        );

        $this->di->bind(
            'Feeds\Superminds\Manager',
            function ($di): Manager {
                return new Manager();
            }
        );
    }
}
