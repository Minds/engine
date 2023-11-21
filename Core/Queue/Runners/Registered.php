<?php

namespace Minds\Core\Queue\Runners;

use Minds\Core\Di\Di;
use Minds\Core\Email\EmailSubscription;
use Minds\Core\Email\Repository;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Queue;
use Minds\Core\Queue\Interfaces\QueueRunner;
use Minds\Core\Subscriptions\Manager as SubscriptionsManager;
use Minds\Entities\User;

class Registered implements QueueRunner
{
    public function run()
    {
        $config = Di::_()->get('Config');
        $subscriptions = $config->get('default_email_subscriptions');
        /** @var Repository $repository */
        $repository = Di::_()->get('Email\Repository');

        /** @var EntitiesBuilder */
        $entitiesBuilder = Di::_()->get(EntitiesBuilder::class);

        $client = Queue\Client::Build();
        $client->setQueue("Registered")
            ->receive(function ($data) use ($subscriptions, $repository, $entitiesBuilder) {
                $data = $data->getData();
                $user_guid = $data['user_guid'];
                $tenant_id = $data['tenant_id'];

                if (!$tenant_id) { // no tenant id means we are on the main site
                    //subscribe to minds channel
                    /** @var User $subscriber */
                    $subscriber = $entitiesBuilder->single($user_guid);
                    $subscriber->subscribe('100000000000000519');

                    echo "[registered]: User registered $user_guid\n";

                    foreach ($subscriptions as $subscription) {
                        $sub = array_merge($subscription, ['userGuid' => $user_guid]);
                        $repository->add(new EmailSubscription($sub));
                    }

                    echo "[registered]: subscribed {$user_guid} to default email notifications \n";
                    return;
                }

                /**
                 * @var MultiTenantBootService $multiTenantBootService
                 */
                $multiTenantBootService = Di::_()->get(MultiTenantBootService::class);
                $multiTenantBootService->bootFromTenantId($tenant_id);

                /**
                 * @var User $user
                 */
                $user = $entitiesBuilder->single($user_guid);

                /**
                 * @var FeaturedEntityService $featuredEntityService
                 */
                $featuredEntityService = Di::_()->get(FeaturedEntityService::class);

                $featuredUsers = $featuredEntityService->getAllFeaturedEntities($tenant_id);
                foreach ($featuredUsers as $featuredUser) {
                    if (!$featuredUser->autoSubscribe) {
                        continue;
                    }

                    $user->subscribe($featuredUser->entityGuid);
                }

            });
        $this->run();
    }
}
