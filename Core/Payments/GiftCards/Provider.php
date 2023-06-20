<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\GiftCards\Controllers\Controller;
use Minds\Core\Payments\V2\Manager as PaymentsManager;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(Repository::class, function (Di $di): Repository {
            return new Repository($di->get(MySQL\Client::class), $di->get('Logger'));
        }, ['factory' => true]);
        $this->di->bind(Manager::class, function (Di $di): Manager {
            return new Manager($di->get(Repository::class), $di->get(PaymentsManager::class));
        }, ['factory' => true]);
        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller($di->get(Manager::class));
        });
    }
}
