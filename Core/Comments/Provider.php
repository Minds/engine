<?php
namespace Minds\Core\Comments;

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
        }, ['useFactory' => false]);
    }
}
