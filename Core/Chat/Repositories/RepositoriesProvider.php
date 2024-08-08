<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\InMemoryCache;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;

class RepositoriesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            RoomRepository::class,
            fn (Di $di): RoomRepository => new RoomRepository(
                inMemoryCache: $di->get(InMemoryCache::class),
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            ),
            ['useFactory' => true]
        );
        $this->di->bind(
            MessageRepository::class,
            fn (Di $di): MessageRepository => new MessageRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            ),
            ['useFactory' => true]
        );
        $this->di->bind(
            ReceiptRepository::class,
            fn (Di $di): ReceiptRepository => new ReceiptRepository(
                roomRepository: $di->get(RoomRepository::class),
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger')
            ),
            ['useFactory' => true]
        );
    }
}
