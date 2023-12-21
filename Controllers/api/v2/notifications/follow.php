<?php

/**
 * Minds Post Notifications endpoint
 *
 * @author emi
 */

namespace Minds\Controllers\api\v2\notifications;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notification\PostSubscriptions\Manager;
use Minds\Core\Notifications\PostSubscriptions\Controllers\PostSubscriptionsController;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Session;
use Minds\Interfaces;

class follow implements Interfaces\Api
{
    public function __construct(
        private ?PostSubscriptionsController $controller = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        $this->controller ??= Di::_()->get(PostSubscriptionsController::class);
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }

    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        $user = Session::getLoggedinUser();

        $postSubscription = $this->controller->getPostSubscription(
            entityGuid: (int) $pages[0],
            loggedInUser: $user,
        );

        return Factory::response([
            'postSubscription' => [
                'following' => $postSubscription->frequency !== PostSubscriptionFrequencyEnum::NEVER,
            ]
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return void
     */
    public function put($pages)
    {
        $entityGuid = (int) $pages[0];
        $this->upsert($entityGuid, PostSubscriptionFrequencyEnum::ALWAYS);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        $entityGuid = (int) $pages[0];
        $this->upsert($entityGuid, PostSubscriptionFrequencyEnum::NEVER);
    }

    private function upsert(int $entityGuid, PostSubscriptionFrequencyEnum $frequency)
    {
        $user = Session::getLoggedinUser();

        $user = Session::getLoggedinUser();

        $this->controller->updatePostSubscription(
            entityGuid: (int) $entityGuid,
            frequency: $frequency,
            loggedInUser: $user,
        );

        $entity = $this->entitiesBuilder->single($entityGuid);

        if ($entity && $entity->entity_guid) {
            $this->controller->updatePostSubscription(
                entityGuid: (int) $entity->entity_guid,
                frequency: $frequency,
                loggedInUser: $user,
            );
        }

        Factory::response([
            'done' => true,
        ]);
    }
}
