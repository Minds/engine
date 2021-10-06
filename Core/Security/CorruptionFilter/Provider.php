<?php
namespace Minds\Core\Security\CorruptionFilter;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind('Security\CorruptionFilter\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
    }
}
