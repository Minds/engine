<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\Payments\Lago;

use Minds\Cli\Controller as CliController;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Lago\Manager as LagoManager;
use Minds\Interfaces\CliControllerInterface;

class Customers extends CliController implements CliControllerInterface
{
    public function help($command = null)
    {
        // TODO: Implement help() method.
    }

    public function exec()
    {
        // TODO: Implement exec() method.
    }

    public function createTestCustomer(): void
    {
        /**
         * @var LagoManager $lagoManager
         */
        $lagoManager = Di::_()->get(LagoManager::class);

        $lagoManager->createCustomer((int) $this->getOpt('customer_id'));
    }
}
