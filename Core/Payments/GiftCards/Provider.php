<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;

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
            return new Manager($di->get(Repository::class));
        }, ['factory' => true]);
    }
}
