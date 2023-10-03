<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas;

use Minds\Core\Data\MySQL\Client as MySqlClient;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Storage\Quotas\Controllers\Controller;
use Minds\Core\Storage\Quotas\Repositories\MySqlRepository;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            Repositories\MySqlRepository::class,
            fn(Di $di): MySqlRepository => new MySqlRepository(
                $di->get(MySqlClient::class),
                $di->get('Logger')
            )
        );

        $this->di->bind(
            Manager::class,
            fn(Di $di): Manager => new Manager(
                $di->get('Storage'),
                $di->get(MySqlRepository::class),
                $di->get('Config'),
                $di->get('Logger')
            )
        );

        $this->di->bind(
            Controller::class,
            fn(Di $di): Controller => new Controller(
                $di->get(Manager::class),
                $di->get('Logger')
            )
        );
    }
}
