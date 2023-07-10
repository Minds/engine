<?php
namespace Minds\Core\Groups\V2;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Groups\Membership as LegacyMembership;

class Provider extends DiProvider
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->di->bind(Membership\Repository::class, function ($di) {
            return new Membership\Repository(
                mysqlClient: $di->get(MySQL\Client::class),
                logger: $di->get('Logger'),
                cache: $di->get('Cache\PsrWrapper')
            );
        });
        $this->di->bind(Membership\Manager::class, function ($di) {
            return new Membership\Manager(
                repository: $di->get(Membership\Repository::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                acl: $di->get('Security\ACL'),
                legacyMembership: new LegacyMembership(),
                experimentsManager: $di->get('Experiments\Manager'),
            );
        });
    }
}
