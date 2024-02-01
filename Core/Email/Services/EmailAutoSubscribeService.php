<?php
declare(strict_types=1);

namespace Minds\Core\Email\Services;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Email\EmailSubscription;
use Minds\Core\Email\Repository;

class EmailAutoSubscribeService
{
    public function __construct(
        private readonly Repository $repository,
        private readonly Config     $config
    ) {
    }

    /**
     * @param int $userGuid
     * @return void
     * @throws Exception
     */
    public function subscribeToDefaultEmails(int $userGuid): void
    {
        $subscriptions = $this->config->get('default_email_subscriptions');

        foreach ($subscriptions as $subscription) {
            $sub = array_merge($subscription, [
                'userGuid' => $userGuid,
            ]);
            $this->repository->add(new EmailSubscription($sub));
        }
    }
}
