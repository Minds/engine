<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Repositories;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Config\Config;

class RepositoriesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(HashtagExclusionRepository::class, function (Di $di): HashtagExclusionRepository {
            return new HashtagExclusionRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            );
        });
    }
}
