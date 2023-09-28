<?php

declare(strict_types=1);

namespace Minds\Core\Entities;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Entities\Repositories\CassandraRepository;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;

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
            return new CassandraRepository(
                $di->get('Database\Cassandra\Entities'),
                $di->get('Database\Cassandra\Data\Lookup'),
                $di->get('Database\Cassandra\Indexes'),
            );
        }, [ 'useFactory' => true]);
    }
}
