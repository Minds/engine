<?php

declare(strict_types=1);

namespace Minds\Core\Authentication;

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
            'Authentication\Controller',
            function ($di): Controller {
                return new Controller();
            },
            [
                'useFactory' => false
            ]
        );

        $this->di->bind(
            'Authentication\Manager',
            function ($di): Manager {
                return new Manager();
            },
            [
                'useFactory' => false
            ]
        );
    }
}
