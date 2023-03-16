<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Partners;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(
            Manager::class,
            function ($di): Manager {
                return new Manager();
            },
            [
                'useFactory' => true
            ]
        );

        $this->di->bind(
            Repository::class,
            function ($di): Repository {
                return new Repository();
            },
            [
                'useFactory' => true
            ]
        );
    }
}
