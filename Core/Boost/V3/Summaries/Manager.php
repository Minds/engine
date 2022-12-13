<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use Minds\Core\Di\Di;

class Manager
{
    public function __construct(
        private ?Repository $repository = null
    ) {
        $this->$repository ??= Di::_()->get('Boost\V3\Summaries\Repository');
    }
}
