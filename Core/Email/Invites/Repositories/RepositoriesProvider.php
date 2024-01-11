<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Repositories;

use Minds\Common\Jwt;
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
            InvitesRepository::class,
            fn (Di $di): InvitesRepository => new InvitesRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                logger: $di->get('Logger'),
                config: $di->get('Config'),
                jwt: (new Jwt())->setKey(file_get_contents($di->get('Config')->get('encryptionKeys')['email']['private']))
            )
        );

    }
}
