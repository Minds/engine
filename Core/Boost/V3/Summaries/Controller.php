<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use Minds\Core\Di\Di;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Boost\V3\Summaries\Manager');
    }
}
