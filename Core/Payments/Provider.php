<?php
declare(strict_types=1);

namespace Minds\Core\Payments;

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
        $this->di->bind('Payments\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
        $this->di->bind(Manager::class, function ($di): Manager {
            return new Manager();
        });
    }
}
