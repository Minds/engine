<?php
declare(strict_types=1);

/**
 * Minds Onboarding v5 Provider.
 */
namespace Minds\Core\Onboarding\V5;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(GraphQL\Controllers\Controller::class, function (Di $di) {
            return new GraphQL\Controllers\Controller();
        }, ['useFactory' => false]);

        $this->di->bind(Manager::class, function (Di $di) {
            return new Manager();
        }, ['useFactory' => false]);

        $this->di->bind(Repository::class, function (Di $di): Repository {
            return new Repository($di->get(Client::class), $di->get(Config::class), $di->get('Logger'));
        }, ['factory' => true]);
    }
}
