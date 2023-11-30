<?php
declare(strict_types=1);

namespace Minds\Core\Entities\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

class EntitiesRepositoryFactory
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function getInstance(): EntitiesRepositoryInterface
    {
        return Di::_()->get(EntitiesRepositoryInterface::class);
    }
}
