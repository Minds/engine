<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind("Supermind\Settings\Controller", function ($di) {
            return new Controller();
        }, ['factory' => true]);

        $this->di->bind("Supermind\Settings\Manager", function ($di) {
            return new Manager();
        }, ['factory' => true]);

        $this->di->bind("Supermind\Settings\Repository", function ($di) {
            return new Repositories\CassandraRepository();
        }, ['factory' => true]);
    }
}
