<?php
namespace Minds\Core\Groups\V2;

use Minds\Core\Config\Config;
use Minds\Core\Chat\Services\RoomService as ChatRoomService;
use Minds\Core\Data\MySQL;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\GraphQL\Controllers\GroupChatController;
use Minds\Core\Groups\V2\Services\GroupChatService;
use Minds\Core\Recommendations\Algorithms\SuggestedGroups\SuggestedGroupsRecommendationsAlgorithm;

class Provider extends DiProvider
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        // Membership
        $this->di->bind(Membership\Repository::class, function ($di) {
            return new Membership\Repository(
                mysqlClient: $di->get(MySQL\Client::class),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
                cache: $di->get('Cache\PsrWrapper')
            );
        });

        $this->di->bind(Membership\Manager::class, function ($di) {
            return new Membership\Manager(
                repository: $di->get(Membership\Repository::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                acl: $di->get('Security\ACL'),
                groupRecsAlgo: new SuggestedGroupsRecommendationsAlgorithm(),
            );
        });

        // Group chat
        $this->di->bind(GroupChatService::class, function ($di): GroupChatService {
            return new GroupChatService(
                chatRoomService: $di->get(ChatRoomService::class),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                saveAction: new SaveAction(),
                logger: $di->get('Logger')
            );
        });

        $this->di->bind(GroupChatController::class, function ($di): GroupChatController {
            return new GroupChatController(
                groupChatService: $di->get(GroupChatService::class)
            );
        });
    }
}
