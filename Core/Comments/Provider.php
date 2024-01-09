<?php

namespace Minds\Core\Comments;

use Minds\Core\Config\Config;
use Minds\Core\Di;

class Provider extends Di\Provider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind('Comments\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => true]);
        $this->di->bind(RelationalRepository::class, function ($di) {
            return new RelationalRepository(
                $di->get('Database\MySQL\Client'),
                $di->get(Config::class),
                $di->get('Logger')
            );
        });
    }
}
