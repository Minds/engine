<?php

namespace Minds\Entities;

use Minds\Core\Di\Provider;
use Minds\Core\Counters;
use Minds\Core\Entities;
use Minds\Core\EntitiesBuilder;

class EntitiesProvider extends Provider
{
    /**
     * Registers providers onto DI
     * @return void
     */
    public function register()
    {
        $this->di->bind('Entities', function ($di) {
            return new Entities();
        }, ['useFactory' => true]);
        $this->di->bind('EntitiesBuilder', function ($di) {
            return new EntitiesBuilder();
        }, ['useFactory' => true]);
        $this->di->bind('Entities\Counters', function ($di) {
            return new Counters();
        }, ['useFactory' => true]);
        $this->di->bind('Entities\Factory', function ($di) {
            return new EntitiesFactory();
        }, ['useFactory' => true]);
        $this->di->bind('PropagateProperties', function ($di) {
            return new Entities\PropagateProperties();
        }, ['useFactory' => true]);
    }
}
