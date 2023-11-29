<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Types\Factories;

use Minds\Core\Di\Di;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Factory;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;

class RssFeedInputFactory
{
    /**
     * @param string $url
     * @param User $user
     * @return RssFeed
     * @throws NoTenantFoundException
     */
    #[Factory(name: 'RssFeedInput')]
    public function createRssFeed(
        string $url,
        #[InjectUser] User $user
    ): RssFeed {
        $tenantId = Di::_()->get('Config')->get('tenant_id') ?? null;

        return new RssFeed(
            feedId: 0,
            userGuid: (int) $user->getGuid(),
            title: '',
            url: $url,
            tenantId: $tenantId,
        );

    }
}
