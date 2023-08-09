<?php

namespace Minds\Core\Monetization\Partners;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Monetization\Partners\Manager', function ($di) {
            return new Manager();
        });
        $this->di->bind('Monetization\Partners\Controllers', function ($di) {
            return new Controllers();
        });

        $this->di->bind(RelationalRepository::class, function (Di $di): RelationalRepository {
            return new RelationalRepository(
                $di->get('Database\MySQL\Client'),
                $di->get('Logger')
            );
        });
    }
}
