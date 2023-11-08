<?php

namespace Minds\Core\Queue\Runners;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Email\EmailSubscription;
use Minds\Core\Email\Repository;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Queue;
use Minds\Core\Queue\Interfaces\QueueRunner;
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

                //subscribe to minds channel
                /** @var User */
                $subscriber = $entitiesBuilder->single($user_guid);
                $subscriber->subscribe('100000000000000519');

                echo "[registered]: User registered $user_guid\n";

                foreach ($subscriptions as $subscription) {
                    $sub = array_merge($subscription, ['userGuid' => $user_guid]);
                    $repository->add(new EmailSubscription($sub));
                }

                echo "[registered]: subscribed {$user_guid} to default email notifications \n";
            });
        $this->run();
    }
}
