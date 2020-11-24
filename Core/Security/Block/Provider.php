<?php
namespace Minds\Core\Security\Block;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind('Security\Block\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
    }
}
