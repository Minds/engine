<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Services;

use Minds\Core\Admin\Repositories\HashtagExclusionRepository;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Entities\Actions\Delete as DeleteAction;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Entities\Resolver as EntitiesResolver;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            ModerationService::class,
            function (Di $di): ModerationService {
                return new ModerationService(
                    rolesService: $di->get(RolesService::class),
                    channelsBanManager: $di->get('Channels\Ban'),
                    deleteAction: new DeleteAction(),
                    commentManager: $di->get('Comments\Manager'),
                    entitiesBuilder: $di->get(EntitiesBuilder::class),
                    entitiesResolver: $di->get(EntitiesResolver::class),
                    acl: $di->get('Security\ACL')
                );
            }
        );

        $this->di->bind(
            HashtagExclusionService::class,
            function (Di $di): HashtagExclusionService {
                return new HashtagExclusionService(
                    repository: $di->get(HashtagExclusionRepository::class),
                    cache: $di->get('Hashtags\Trending\Cache')
                );
            }
        );

        $this->di->bind(
            UsersService::class,
            function (Di $di): UsersService {
                return new UsersService(
                    rolesService: $di->get(RolesService::class),
                );
            }
        );
    }
}
