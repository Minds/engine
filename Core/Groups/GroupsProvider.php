<?php
/**
 * Minds Groups Provider
 */

namespace Minds\Core\Groups;

use Minds\Core\Di\Provider;
use Minds\Core\Groups\AdminQueue;
use Minds\Core\Groups\Feeds;

class GroupsProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Groups\AdminQueue', function ($di) {
            return new AdminQueue(
                client: $di->get('Database\Cassandra\Cql'),
                scroll: $di->get('Database\Cassandra\Cql\Scroll'),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                acl: $di->get('Security\ACL'),
            );
        }, [ 'useFactory'=> true ]);

        $this->di->bind('Groups\Feeds', function ($di) {
            return new Feeds();
        }, [ 'useFactory'=> false ]);
    }
}
