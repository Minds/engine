<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas\Controllers;

use Minds\Core\Log\Logger;
use Minds\Core\Storage\Quotas\Manager;

class Controller
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Logger $logger
    ) {
    }
}
