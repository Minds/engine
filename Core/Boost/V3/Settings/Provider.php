<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Settings;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind("Boost\V3\Settings\Controller", function ($di) {
            return new Controller();
        }, ['factory' => true]);

        $this->di->bind("Boost\V3\Settings\Manager", function ($di) {
            return new Manager();
        }, ['factory' => true]);

        $this->di->bind("Boost\V3\Settings\Repository", function ($di) {
            return new Repositories\UserStorageRepository();
        }, ['factory' => true]);
    }
}
