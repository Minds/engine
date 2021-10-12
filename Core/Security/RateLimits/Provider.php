<?php
namespace Minds\Core\Security\RateLimits;

use Minds\Core\Di;

class Provider extends Di\Provider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind('Security\RateLimits\InteractionsLimiter', function ($di) {
            return new InteractionsLimiter();
        }, ['useFactory' => false]);

        $this->di->bind('Security\RateLimits\KeyValueLimiter', function ($di) {
            return new KeyValueLimiter();
        }, ['useFactory' => false]);
    }
}
