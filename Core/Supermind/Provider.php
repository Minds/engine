<?php

namespace Minds\Core\Supermind;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind("Supermind\Controller", function ($di) {
            return new Controller();
        });
        $this->di->bind("Supermind\Manager", function ($di) {
            return new Manager();
        });
        $this->di->bind("Supermind\Repository", function ($di) {
            return new Repository();
        }, ['factory' => true]);
        $this->di->bind("Supermind\Notifications\Push\Manager", function ($di) {
            return new Notifications\Push\Manager();
        }, ['factory' => true]);
    }
}
