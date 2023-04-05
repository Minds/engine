<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2;

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
            Manager::class,
            function (Di $di): Manager {
                return new Manager();
            },
            ['factory' => true]
        );

        $this->di->bind(
            Repository::class,
            function (Di $di): Repository {
                return new Repository();
            },
            ['factory' => true]
        );
    }
}
