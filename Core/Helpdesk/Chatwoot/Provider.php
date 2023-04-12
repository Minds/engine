<?php
declare(strict_types=1);

namespace Minds\Core\Helpdesk\Chatwoot;

use Minds\Core\Di\Provider as DiProvider;

/**
 * Chatwoot Provider
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(Manager::class, function ($di) {
            return new Manager();
        });

        $this->di->bind(Controller::class, function ($di) {
            return new Controller();
        });
    }
}
