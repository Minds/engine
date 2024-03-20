<?php

declare(strict_types=1);

namespace Minds\Core\Entities;

use Minds\Core\Chat\Services\MessageService as ChatMessageService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Entities\Delegates\ChatMessageResolverDelegate;
use Minds\Core\Entities\Repositories\CassandraRepository;
use Minds\Core\Entities\Repositories\EntitiesRepositoryFactory;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;
use Minds\Core\Entities\Repositories\MySQLRepository;

class Provider extends \Minds\Core\Di\Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            'Entities\Controller',
            function ($di) {
                return new Controller();
            }
        );

        $this->di->bind(Resolver::class, function ($di) {
            return new Resolver();
        });

        $this->di->bind(GuidLinkResolver::class, function ($di): GuidLinkResolver {
            return new GuidLinkResolver();
        });

        //

        $this->di->bind(EntitiesRepositoryInterface::class, function (Di $di): EntitiesRepositoryInterface {
            $config = $di->get(Config::class);

            if ($config->get('tenant_id')) {
                return $di->get(MySQLRepository::class);
            } else {
                return $di->get(CassandraRepository::class);
            }
        });

        $this->di->bind(
            EntitiesRepositoryFactory::class,
            fn (Di $di): EntitiesRepositoryFactory => new EntitiesRepositoryFactory()
        );

        $this->di->bind(CassandraRepository::class, function (Di $di): CassandraRepository {
            return new CassandraRepository(
                $di->get('Database\Cassandra\Entities'),
                $di->get('Database\Cassandra\Data\Lookup'),
                $di->get('Database\Cassandra\Indexes'),
            );
        }, [ 'useFactory' => true]);

        $this->di->bind(MySQLRepository::class, function (Di $di): MySQLRepository {
            return new MySQLRepository(
                config: $di->get(Config::class),
                activeSession: $di->get('Sessions\ActiveSession'),
                mysqlClient: $di->get('Database\MySQL\Client'),
                logger: $di->get('Logger')
            );
        }, [ 'useFactory' => true]);

        // Resolver Delegates
        $this->di->bind(
            ChatMessageResolverDelegate::class,
            fn (Di $di): ChatMessageResolverDelegate => new ChatMessageResolverDelegate(
                chatMessageService: $di->get(ChatMessageService::class)
            ),
            [ 'useFactory' => true]
        );
    }
}
