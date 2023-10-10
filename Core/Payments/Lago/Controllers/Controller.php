<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Controllers;

use Minds\Core\Log\Logger;
use Minds\Core\Payments\Lago\Manager;

class Controller
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Logger $logger
    ) {
    }
}
